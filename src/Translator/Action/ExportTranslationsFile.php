<?php

namespace Skeletor\Translator\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Skeletor\Translator\Service\TranslationFileExporter;

/**
 * CLI action: regenerate the JS translations module from the `translation` table.
 *
 * Map it in the app's cliMap and run it after a manual DB import (admin edits regenerate
 * automatically via TranslatorCrudService):
 *
 *   'exportTranslations' => \Skeletor\Translator\Action\ExportTranslationsFile::class,
 *   php public/cli.php exportTranslations run
 *
 * ('Action' in the FQN makes CliSkeletor invoke it with a request/response pair.)
 */
class ExportTranslationsFile
{
    public function __construct(private TranslationFileExporter $exporter)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $written = $this->exporter->export();

        echo $written
            ? 'Translations JS file regenerated.' . PHP_EOL
            : 'Translations JS file already up to date - no change.' . PHP_EOL;

        return $response;
    }
}
