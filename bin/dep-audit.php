#!/usr/bin/env php
<?php
/**
 * Dependency quality audit.
 *
 * Scores every locked package on maintenance, bus factor and adoption, then weights that score by
 * how hard this codebase leans on it. A mediocre package referenced twice is not the same problem
 * as a mediocre package referenced in a hundred files, and a raw quality number will never tell
 * you which is which. RISK is the column that matters.
 *
 * Deliberately dependency-free: an audit tool that needs `composer install` to run cannot tell you
 * anything on the day composer is the thing that broke.
 *
 * Usage:
 *   php bin/dep-audit.php                      full table
 *   php bin/dep-audit.php --direct             only packages named in composer.json
 *   php bin/dep-audit.php --json               machine-readable, for CI
 *   php bin/dep-audit.php --fail-under=40      exit 1 if any DIRECT dep scores below N
 *   php bin/dep-audit.php --offline            lockfile signals only, no network
 *   php bin/dep-audit.php --refresh            ignore the cache
 */

const CACHE_TTL = 86400;      // Packagist stats move slowly; a day is plenty
const HTTP_TIMEOUT = 10;

$opts = parseArgs($argv);
$root = dirname(__DIR__);

$lockPath = $root . '/composer.lock';
$jsonPath = $root . '/composer.json';
if (!is_file($lockPath) || !is_file($jsonPath)) {
    fwrite(STDERR, "composer.json / composer.lock not found in " . $root . "\n");
    exit(2);
}

$lock = json_decode(file_get_contents($lockPath), true);
$cj   = json_decode(file_get_contents($jsonPath), true);
if (!is_array($lock) || !is_array($cj)) {
    fwrite(STDERR, "Could not parse composer.json / composer.lock\n");
    exit(2);
}

$direct = [];
foreach (($cj['require'] ?? []) as $name => $constraint) {
    if ($name !== 'php' && !str_starts_with($name, 'ext-') && !str_starts_with($name, 'lib-')) {
        $direct[$name] = $constraint;
    }
}

$allPackages = $lock['packages'] ?? [];
$packages = $allPackages;
if ($opts['direct']) {
    $packages = array_values(array_filter($allPackages, static function ($p) use ($direct) {
        return isset($direct[$p['name']]);
    }));
}

$coupling = scanCoupling($root, $allPackages);

$rows = [];
foreach ($packages as $p) {
    $name = $p['name'];
    $meta = $opts['offline'] ? null : fetchPackagist($name, $root, $opts['refresh']);
    $rows[] = buildRow(
        $p,
        $meta,
        $coupling[$name] ?? 0,
        isset($direct[$name]),
        $direct[$name] ?? null
    );
}

usort($rows, static function ($a, $b) {
    return $b['risk'] <=> $a['risk'];
});

if ($opts['json']) {
    echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
} else {
    renderTable($rows);
}

if ($opts['failUnder'] !== null) {
    $bad = array_filter($rows, static function ($r) use ($opts) {
        return $r['direct'] && $r['score'] < $opts['failUnder'];
    });
    if (count($bad) > 0) {
        fwrite(STDERR, sprintf(
            "\nFAIL: %d direct dependency(ies) scored below %d: %s\n",
            count($bad),
            $opts['failUnder'],
            implode(', ', array_column($bad, 'name'))
        ));
        exit(1);
    }
}
exit(0);


// ---------------------------------------------------------------- scoring

/**
 * 0-100. Every component is capped and additive, so a single bad signal cannot dominate and the
 * reason for a score is always reconstructable from the flags column.
 */
function buildRow(array $p, ?array $meta, int $coupling, bool $isDirect, ?string $constraint): array
{
    $name     = $p['name'];
    $flags    = [];
    $score    = 0;
    $lockedAt = substr((string)($p['time'] ?? ''), 0, 10);

    // --- abandoned: nothing else matters
    $abandoned = $p['abandoned'] ?? ($meta['abandoned'] ?? null);
    if ($abandoned !== null && $abandoned !== false) {
        $flags[] = is_string($abandoned) ? 'ABANDONED->' . $abandoned : 'ABANDONED';
        return finish($name, 0, $flags, $coupling, $isDirect, $constraint, $p, $meta, $lockedAt);
    }

    // --- maintenance (0-35): how long since the project last shipped anything
    $lastRelease = $meta['last_release'] ?? ($p['time'] ?? null);
    $ageDays = $lastRelease ? (int)((time() - strtotime($lastRelease)) / 86400) : null;
    if ($ageDays === null) {
        $score += 15;
        $flags[] = 'no-date';
    } elseif ($ageDays <= 180) {
        $score += 35;
    } elseif ($ageDays <= 365) {
        $score += 28;
    } elseif ($ageDays <= 730) {
        $score += 18;
        $flags[] = 'stale>1y';
    } elseif ($ageDays <= 1460) {
        $score += 8;
        $flags[] = 'stale>2y';
    } else {
        $flags[] = 'stale>4y';
    }

    // --- bus factor (0-25): one maintainer is the most common way a dependency quietly dies
    $maintainers = $meta['maintainers'] ?? null;
    if ($maintainers === null) {
        $score += 12;
    } elseif ($maintainers >= 5) {
        $score += 25;
    } elseif ($maintainers >= 3) {
        $score += 22;
    } elseif ($maintainers === 2) {
        $score += 16;
    } else {
        $score += 6;
        $flags[] = 'bus-factor-1';
    }

    // --- adoption (0-25), log scaled: 1k/mo vs 10k/mo matters far more than 100k vs 1M
    $monthly = $meta['monthly'] ?? null;
    if ($monthly === null) {
        $score += 12;
    } else {
        $score += (int)min(25, max(0, round((log10(max($monthly, 1)) - 1) * 8)));
        if ($monthly < 1000) {
            $flags[] = 'low-adoption';
        }
    }

    // --- issue health (0-10)
    $issues = $meta['open_issues'] ?? null;
    $stars  = $meta['stars'] ?? null;
    if ($issues === null || $stars === null) {
        $score += 5;
    } elseif ($stars > 0 && ($issues / max($stars, 1)) > 0.25) {
        $score += 2;
        $flags[] = 'issue-backlog';
    } else {
        $score += 10;
    }

    // --- licence (0-5)
    if (!empty($p['license'])) {
        $score += 5;
    } else {
        $flags[] = 'no-license';
    }

    // --- constraint hygiene: not scored (it is our bug, not theirs) but always surfaced
    if ($constraint === '*') {
        $flags[] = 'CONSTRAINT-*';
    }

    return finish($name, min(100, $score), $flags, $coupling, $isDirect, $constraint, $p, $meta, $lockedAt);
}

function finish(
    string $name,
    int $score,
    array $flags,
    int $coupling,
    bool $direct,
    ?string $constraint,
    array $p,
    ?array $meta,
    string $lockedAt
): array {
    // Risk = how bad it is, amplified by how much code would have to change to replace it.
    // Log scaled, so 200 references is not treated as ten times worse than 20.
    $risk = (int)round((100 - $score) * (1 + log10(max($coupling, 1))));

    return [
        'name'        => $name,
        'version'     => $p['version'] ?? '?',
        'locked_at'   => $lockedAt,
        'latest'      => $meta['latest'] ?? null,
        'score'       => $score,
        'coupling'    => $coupling,
        'risk'        => $risk,
        'direct'      => $direct,
        'constraint'  => $constraint,
        'monthly'     => $meta['monthly'] ?? null,
        'maintainers' => $meta['maintainers'] ?? null,
        'flags'       => $flags,
    ];
}

// ---------------------------------------------------------------- coupling

/**
 * How many times this codebase names each package's namespace. Uses the PSR-4/PSR-0 roots the
 * package declares in the lockfile, so it needs no hardcoded knowledge of what anything is called.
 */
function scanCoupling(string $root, array $packages): array
{
    $nsMap = [];
    foreach ($packages as $p) {
        $roots = array_merge(
            array_keys($p['autoload']['psr-4'] ?? []),
            array_keys($p['autoload']['psr-0'] ?? [])
        );
        foreach ($roots as $ns) {
            $ns = trim($ns, '\\');
            if ($ns !== '') {
                $nsMap[$ns] = $p['name'];
            }
        }
    }

    $counts = [];
    foreach (['/src', '/config', '/packages', '/bin', '/themes'] as $dir) {
        $path = $root . $dir;
        if (!is_dir($path)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }
            $code = @file_get_contents($file->getPathname());
            if ($code === false) {
                continue;
            }
            foreach ($nsMap as $ns => $pkg) {
                $n = substr_count($code, $ns . '\\');
                if ($n > 0) {
                    $counts[$pkg] = ($counts[$pkg] ?? 0) + $n;
                }
            }
        }
    }

    return $counts;
}

// ---------------------------------------------------------------- packagist

function fetchPackagist(string $name, string $root, bool $refresh): ?array
{
    $cacheDir = $root . '/data/cache/dep-audit';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }
    $cacheFile = $cacheDir . '/' . str_replace('/', '__', $name) . '.json';

    if (!$refresh && is_file($cacheFile) && (time() - filemtime($cacheFile)) < CACHE_TTL) {
        $cached = json_decode((string)file_get_contents($cacheFile), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout'       => HTTP_TIMEOUT,
            'header'        => "User-Agent: skeletor-dep-audit/1.0\r\n",
            'ignore_errors' => true,
        ],
    ]);

    $raw = @file_get_contents('https://packagist.org/packages/' . $name . '.json', false, $ctx);
    if ($raw === false) {
        fwrite(STDERR, '  ! could not reach packagist for ' . $name . "\n");
        return null;
    }

    $data = json_decode($raw, true);
    if (!isset($data['package'])) {
        return null;
    }
    $pkg = $data['package'];

    // Latest stable = newest non-branch, non-prerelease version by release date.
    $latest = null;
    $latestTime = null;
    foreach (($pkg['versions'] ?? []) as $v => $info) {
        if (str_contains($v, 'dev') || preg_match('/(alpha|beta|RC)/i', $v)) {
            continue;
        }
        $t = $info['time'] ?? null;
        if ($t !== null && ($latestTime === null || $t > $latestTime)) {
            $latestTime = $t;
            $latest = $v;
        }
    }

    $meta = [
        'latest'       => $latest,
        'last_release' => $latestTime,
        'monthly'      => $pkg['downloads']['monthly'] ?? null,
        'total'        => $pkg['downloads']['total'] ?? null,
        'favers'       => $pkg['favers'] ?? null,
        'stars'        => $pkg['github_stars'] ?? null,
        'open_issues'  => $pkg['github_open_issues'] ?? null,
        'maintainers'  => isset($pkg['maintainers']) ? count($pkg['maintainers']) : null,
        'abandoned'    => $pkg['abandoned'] ?? null,
        'repository'   => $pkg['repository'] ?? null,
    ];

    @file_put_contents($cacheFile, json_encode($meta));

    return $meta;
}

// ---------------------------------------------------------------- output

function renderTable(array $rows): void
{
    printf(
        "%-36s %-12s %-11s %5s %6s %5s  %s\n",
        'PACKAGE',
        'LOCKED',
        'RELEASED',
        'SCORE',
        'REFS',
        'RISK',
        'FLAGS'
    );
    echo str_repeat('-', 122), "\n";

    foreach ($rows as $r) {
        printf(
            "%-34s%s %-12s %-11s %5d %6d %5d  %s\n",
            substr($r['name'], 0, 34),
            $r['direct'] ? ' *' : '  ',
            substr((string)$r['version'], 0, 12),
            $r['locked_at'] !== '' ? $r['locked_at'] : '?',
            $r['score'],
            $r['coupling'],
            $r['risk'],
            implode(' ', $r['flags'])
        );
    }

    $directCount = count(array_filter($rows, static function ($r) {
        return $r['direct'];
    }));

    printf(
        "\n%d packages (%d direct, marked *).  SCORE 0-100, higher is better.  "
        . "RISK = (100-score) scaled by how much code references it.\n",
        count($rows),
        $directCount
    );
    echo "Sorted by RISK: the top of this list is where cleanup buys the most.\n";
}

// ---------------------------------------------------------------- args

function parseArgs(array $argv): array
{
    $o = [
        'json'      => false,
        'direct'    => false,
        'offline'   => false,
        'refresh'   => false,
        'failUnder' => null,
    ];

    foreach (array_slice($argv, 1) as $a) {
        if ($a === '--json') {
            $o['json'] = true;
        } elseif ($a === '--direct') {
            $o['direct'] = true;
        } elseif ($a === '--offline') {
            $o['offline'] = true;
        } elseif ($a === '--refresh') {
            $o['refresh'] = true;
        } elseif (str_starts_with($a, '--fail-under=')) {
            $o['failUnder'] = (int)substr($a, 13);
        } elseif ($a === '--help' || $a === '-h') {
            echo "Usage: php bin/dep-audit.php [--direct] [--json] [--offline] [--refresh] [--fail-under=N]\n";
            exit(0);
        }
    }

    return $o;
}
