#!/usr/bin/env php
<?php
/**
 * Scan template files for translation strings and insert them into the database.
 *
 * Usage:
 *   php bin/collect-translations.php                    # Scan and collect for all languages
 *   php bin/collect-translations.php --language=sr-sr   # Collect for specific language only
 *   php bin/collect-translations.php --dry-run          # Show what would be collected without inserting
 *   php bin/collect-translations.php --scan-path=/path  # Scan additional path (besides themes/)
 *
 * Scans for patterns:
 *   $this->t('string')
 *   $this->t("string")
 */

require __DIR__ . '/../config/constants.php';
require APP_PATH . '/vendor/autoload.php';

// Parse CLI arguments
$options = getopt('', ['language:', 'dry-run', 'scan-path:', 'help']);

if (isset($options['help'])) {
    echo <<<HELP
    Translation String Collector

    Scans template files for \$this->t('...') calls and inserts
    missing strings into the translation table.

    Options:
      --language=CODE   Collect for specific language only (e.g. sr-sr)
      --dry-run         Show found strings without inserting into database
      --scan-path=PATH  Additional path to scan (can be used multiple times)
      --help            Show this help message

    Examples:
      php bin/collect-translations.php
      php bin/collect-translations.php --language=sr-sr
      php bin/collect-translations.php --dry-run
      php bin/collect-translations.php --scan-path=/vagrant/packages

    HELP;
    exit(0);
}

$dryRun = isset($options['dry-run']);
$targetLanguageCode = $options['language'] ?? null;

// Paths to scan for templates
$scanPaths = [
    APP_PATH . '/themes',
];

// Add custom scan paths
if (isset($options['scan-path'])) {
    $extra = is_array($options['scan-path']) ? $options['scan-path'] : [$options['scan-path']];
    $scanPaths = array_merge($scanPaths, $extra);
}

// Boot the DI container
putenv('APPLICATION=backend');
putenv('APPLICATION_ENV=development');

try {
    $container = require APP_PATH . '/config/bootstrap.php';
} catch (\Exception $e) {
    fwrite(STDERR, "Failed to boot application: {$e->getMessage()}\n");
    exit(1);
}

$repository = $container->get(\Skeletor\Translator\Repository\TranslationRepository::class);

// ── Step 1: Scan template files ──────────────────────────────────────────────

echo "Scanning for translation strings...\n";

$strings = [];
$fileCount = 0;

foreach ($scanPaths as $scanPath) {
    if (!is_dir($scanPath)) {
        echo "  Warning: Path not found, skipping: {$scanPath}\n";
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($scanPath, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $content = file_get_contents($file->getPathname());

        // Match $this->t('...') and $this->t("...")
        // Handles escaped quotes inside strings
        preg_match_all(
            '/\$this->t\(\s*[\']((?:[^\'\\\\]|\\\\.)*)[\']\s*\)/',
            $content,
            $singleQuoteMatches
        );
        preg_match_all(
            '/\$this->t\(\s*["]((?:[^"\\\\]|\\\\.)*)["]\s*\)/',
            $content,
            $doubleQuoteMatches
        );

        $found = array_merge($singleQuoteMatches[1], $doubleQuoteMatches[1]);

        if (!empty($found)) {
            $fileCount++;
            foreach ($found as $string) {
                // Skip dynamic strings (containing PHP variables)
                if (str_contains($string, '$')) {
                    continue;
                }
                $strings[$string] = ($strings[$string] ?? 0) + 1;
            }
        }
    }
}

echo sprintf("  Found %d unique strings in %d files\n\n", count($strings), $fileCount);

if (empty($strings)) {
    echo "No translation strings found.\n";
    exit(0);
}

// ── Step 2: Get target languages ─────────────────────────────────────────────

if ($targetLanguageCode) {
    $lang = $repository->getLanguageByCode($targetLanguageCode);
    if (!$lang) {
        fwrite(STDERR, "Language '{$targetLanguageCode}' not found in database.\n");
        exit(1);
    }
    $languages = [$lang];
} else {
    $languages = $repository->getAllLanguages();
}

if (empty($languages)) {
    fwrite(STDERR, "No languages found in database. Seed the language table first.\n");
    exit(1);
}

echo sprintf("Collecting for %d language(s): %s\n\n",
    count($languages),
    implode(', ', array_map(fn($l) => $l->name . ' (' . $l->code . ')', $languages))
);

// ── Step 3: Insert missing translations ──────────────────────────────────────

$totalInserted = 0;
$totalSkipped = 0;

foreach ($languages as $language) {
    $inserted = 0;
    $skipped = 0;

    echo "  {$language->name} ({$language->code}):\n";

    foreach ($strings as $string => $count) {
        $existing = $repository->findTranslation($string, $language->getId());

        if ($existing) {
            $skipped++;
            continue;
        }

        if ($dryRun) {
            echo "    [NEW] {$string}\n";
            $inserted++;
            continue;
        }

        try {
            $repository->createTranslation($string, $language);
            $inserted++;
        } catch (\Throwable $e) {
            // Duplicate or other error — skip silently
            $skipped++;
        }
    }

    echo sprintf("    %d new, %d already exist\n", $inserted, $skipped);
    $totalInserted += $inserted;
    $totalSkipped += $skipped;
}

echo sprintf("\nDone.%s Total: %d new, %d already exist.\n",
    $dryRun ? ' (DRY RUN — nothing was inserted)' : '',
    $totalInserted,
    $totalSkipped
);
