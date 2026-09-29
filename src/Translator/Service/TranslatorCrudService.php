<?php

namespace Skeletor\Translator\Service;

use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\TableView\Model\Column;
use Skeletor\Core\TableView\Service\TableView;
use Skeletor\Translator\Repository\TranslationRepository;
use Skeletor\User\Service\Session;

class TranslatorCrudService extends TableView
{
    public function __construct(
        TranslationRepository $repository,
        Session $userSession,
        Logger $logger,
        private Translator $translator,
        private TranslationFileExporter $exporter,
        \Skeletor\Core\Activity\Service\Activity $activity,
    ) {
        parent::__construct($repository, $userSession, $logger, activity: $activity);
    }

    public function compileTableColumns(): array
    {
        $languageFilters = [];
        foreach ($this->translator->getAvailableLanguages() as $language) {
            $languageFilters[$language->id] = ucfirst($this->translator->translate($language->name));
        }
        return [
            ['name' => 'originalString', 'label' => 'Original string'],
            ['name' => 'translatedString', 'label' => 'Translated string'],
            ['name' => 'language', 'label' => 'Language', 'filterData' => $languageFilters],
        ];
    }

    public function prepareEntities($entities): array
    {
        $items = [];
        foreach ($entities as $translation) {
            $items[] = [
                'columns' => [
                    'id' => $translation->getId(),
                    'originalString' => $translation->originalString,
                    'translatedString' => $translation->translatedString,
                    'language' => $translation->language->name ?? '',
                ],
                'id' => $translation->getId(),
            ];
        }
        return $items;
    }

    /**
     * Copy all translations from one language to another (with empty translatedString).
     * Useful when adding a new language — seeds all known strings.
     */
    public function createTranslationsForLanguage(int $targetLanguageId, int $sourceLanguageId = 1): void
    {
        $sourceTranslations = $this->repo->fetchAll(['language' => $sourceLanguageId]);

        foreach ($sourceTranslations as $translation) {
            try {
                $this->repo->create([
                    'language' => $targetLanguageId,
                    'originalString' => $translation->originalString,
                    'translatedString' => '',
                ]);
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to copy translation', [
                    'originalString' => $translation->originalString,
                    'targetLanguageId' => $targetLanguageId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->translator->resetCache();
        $this->regenerateJsFile();
    }

    /**
     * Override create/update/delete to invalidate the translator cache and regenerate
     * the JS translations file consumed by the front-end Translator pair.
     */
    public function create(array $data): mixed
    {
        $result = parent::create($data);
        $this->translator->resetCache();
        $this->regenerateJsFile();
        return $result;
    }

    public function update(array $data): mixed
    {
        $result = parent::update($data);
        $this->translator->resetCache();
        $this->regenerateJsFile();
        return $result;
    }

    public function delete($id): mixed
    {
        $result = parent::delete($id);
        $this->translator->resetCache();
        $this->regenerateJsFile();
        return $result;
    }

    /**
     * Rebuild the JS translations file. The exporter skips the write when nothing changed,
     * so this is cheap. A failure here must never break the admin action, so it's swallowed
     * (and logged) rather than propagated.
     */
    private function regenerateJsFile(): void
    {
        try {
            $this->exporter->export();
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to regenerate translations JS file', ['error' => $e->getMessage()]);
        }
    }
}
