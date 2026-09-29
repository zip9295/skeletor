<?php

namespace Skeletor\Translator\Service;

use Skeletor\Core\Config\Config;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Translator\Repository\TranslationRepository;

/**
 * Generates the JS translations module consumed by the Translator's JS pair
 * (`export const translations = Object.freeze({ ... })`), built from the `translation`
 * table as [ originalString => { languageCode: translatedString } ], empties skipped.
 * Each JS consumer looks up a string by (original, its own target language).
 *
 * Config `translator.jsLanguages` restricts which target languages are emitted; setting
 * it to e.g. ['sr'] keeps the file keyed by the English source string with a single "sr"
 * entry ("English": {"sr": "Serbian"}) and drops reverse-direction rows.
 *
 * Output path comes from config `translator.jsFilePath`, defaulting to the admin theme's
 * config file. A sha256 of the canonical payload is embedded on the first line; export()
 * reads it back and skips the write when nothing changed, so it's cheap to call often
 * (e.g. after every admin edit).
 */
class TranslationFileExporter
{
    private const DEFAULT_RELATIVE_PATH = '/public/assets/backend/js/config/translations.js';

    public function __construct(
        private TranslationRepository $repository,
        private Config $config,
        private ?Logger $logger = null,
    ) {
    }

    /**
     * Regenerate the JS translations file(s). The same content is written to every
     * configured path (front + back for now). Returns true when at least one file was
     * (re)written, false when all were already up to date or no path is configured.
     */
    public function export(): bool
    {
        $paths = $this->resolvePaths();
        if (empty($paths)) {
            $this->logger?->warning('TranslationFileExporter: no output path (config translator.jsFilePath[s]) and APP_PATH is undefined; skipping.');
            return false;
        }

        $data = $this->repository->getGroupedByOriginal($this->resolveLanguages());

        // Optionally key the file by the translated string instead of the original
        // (config translator.jsInvert). Turns "Obriši": {"sr": "Delete"} into
        // "Delete": {"sr": "Obriši"} without touching the DB.
        if ($this->config->get('translator')?->get('jsInvert')) {
            $data = $this->invert($data);
        }

        // Deterministic ordering so the hash is stable regardless of DB row order.
        ksort($data);
        foreach ($data as &$langs) {
            ksort($langs);
        }
        unset($langs);

        $hash = hash('sha256', (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $contents = null; // rendered lazily, only if some file is stale
        $wroteAny = false;
        foreach ($paths as $path) {
            if ($this->currentHash($path) === $hash) {
                continue; // this file is already up to date
            }
            $contents ??= $this->render($data, $hash);
            $this->write($path, $contents);
            $this->logger?->info(sprintf('TranslationFileExporter: wrote %d strings to %s', count($data), $path));
            $wroteAny = true;
        }

        return $wroteAny;
    }

    /**
     * Swap key and value: `[original => [code => translated]]` becomes
     * `[translated => [code => original]]`. With a single target language per entry this
     * simply flips the direction (English key, Serbian value). On a key collision the last
     * entry wins.
     *
     * @param array<string, array<string, string>> $data
     * @return array<string, array<string, string>>
     */
    private function invert(array $data): array
    {
        $out = [];
        foreach ($data as $key => $langs) {
            foreach ($langs as $code => $value) {
                if ($value !== '') {
                    $out[$value][$code] = $key;
                }
            }
        }
        return $out;
    }

    /**
     * Target language codes to include, from config `translator.jsLanguages` (array).
     * Null (unset/empty) includes every language. Restricting to e.g. ['sr'] keeps the
     * file English-keyed by dropping reverse-direction rows (Serbian-source → 'en').
     *
     * @return string[]|null
     */
    private function resolveLanguages(): ?array
    {
        $codes = $this->config->get('translator')?->get('jsLanguages');
        if ($codes instanceof \Traversable) {
            $codes = iterator_to_array($codes);
        }
        return is_array($codes) && $codes !== [] ? array_values($codes) : null;
    }

    /**
     * Output paths from config `translator.jsFilePaths` (array) and/or `translator.jsFilePath`
     * (single), de-duplicated. Falls back to the admin theme's file when nothing is configured.
     *
     * @return string[]
     */
    private function resolvePaths(): array
    {
        $translator = $this->config->get('translator');
        $paths = [];

        $multi = $translator?->get('jsFilePaths');
        if ($multi !== null) {
            // Laminas wraps array config in a Config object; normalise to a plain list.
            $paths = $multi instanceof \Traversable ? iterator_to_array($multi) : (array) $multi;
        }

        $single = $translator?->get('jsFilePath');
        if (is_string($single) && $single !== '') {
            $paths[] = $single;
        }

        $paths = array_values(array_unique(array_filter(
            $paths,
            static fn ($p) => is_string($p) && $p !== '',
        )));

        if (empty($paths) && defined('APP_PATH')) {
            $paths[] = APP_PATH . self::DEFAULT_RELATIVE_PATH;
        }

        return $paths;
    }

    /** The sha256 embedded on the first line of an existing file, or null when absent. */
    private function currentHash(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return null;
        }
        $firstLine = fgets($handle) ?: '';
        fclose($handle);

        return preg_match('/@generated\s+([a-f0-9]{64})/', $firstLine, $m) === 1 ? $m[1] : null;
    }

    /** @param array<string, array<string, string>> $data */
    private function render(array $data, string $hash): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        $lines = [];
        foreach ($data as $original => $langs) {
            $pairs = [];
            foreach ($langs as $code => $translated) {
                $pairs[] = json_encode((string) $code, $flags) . ': ' . json_encode((string) $translated, $flags);
            }
            // e.g.  "English": {"sr": "Serbian"},
            $lines[] = '    ' . json_encode((string) $original, $flags) . ': {' . implode(', ', $pairs) . '},';
        }

        return "// @generated {$hash} - do not edit; regenerated from the `translation` table\n"
            . "export const translations = Object.freeze({\n"
            . ($lines ? implode("\n", $lines) . "\n" : '')
            . "});\n";
    }

    private function write(string $path, string $contents): void
    {
        // Write through an existing target rather than replacing it. A deploy that points
        // this path at shared storage via a symlink needs the symlink to survive the export:
        // renaming a temp file over it would swap the link for a regular file and the
        // generated translations would stop persisting across releases. is_link() is checked
        // first so a symlink whose target does not exist yet (first deploy) is still followed
        // and creates the file it points at, instead of being overwritten here.
        if (is_link($path) || file_exists($path)) {
            file_put_contents($path, $contents, LOCK_EX);
            return;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        // No target yet, so nothing to preserve: create it atomically via a temp file in the
        // same directory, then rename into place.
        $tmp = $path . '.tmp';
        file_put_contents($tmp, $contents, LOCK_EX);
        rename($tmp, $path);
    }
}
