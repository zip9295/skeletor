<?php

namespace Skeletor\Translator\Repository;

use Skeletor\Core\TableView\Repository\TableViewRepository;
use Skeletor\Translator\Entity\Language;
use Skeletor\Translator\Entity\Translation;
use Skeletor\Translator\Factory\TranslationFactory;

class TranslationRepository extends TableViewRepository
{
    const ENTITY = Translation::class;
    const FACTORY = TranslationFactory::class;

    public function getSearchableColumns(): array
    {
        return ['a.originalString', 'a.translatedString'];
    }

    /**
     * Load all translations for a language as a flat key=>value array.
     */
    public function getTranslations(int $languageId): array
    {
        $translations = [];
        $entities = $this->entityManager->getRepository(Translation::class)->findBy(['language' => $languageId]);

        foreach ($entities as $entity) {
            $translations[$entity->originalString] = $entity->translatedString ?? '';
        }

        return $translations;
    }

    /**
     * All non-empty translations grouped for the JS export:
     *   [ originalString => [ languageCode => translatedString ] ].
     * One query; empty translations and rows without a language code are skipped.
     * Ordering is not guaranteed here — the exporter sorts for a stable hash.
     *
     * @param string[]|null $languageCodes When given, only these target language codes
     *        are included (e.g. ['sr'] to keep the file English-keyed and skip reverse-
     *        direction rows). Null includes every language.
     * @return array<string, array<string, string>>
     */
    public function getGroupedByOriginal(?array $languageCodes = null): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('t.originalString AS original', 't.translatedString AS translated', 'l.code AS code')
            ->from(Translation::class, 't')
            ->innerJoin('t.language', 'l');

        if (!empty($languageCodes)) {
            $qb->where('l.code IN (:codes)')->setParameter('codes', array_values($languageCodes));
        }

        $rows = $qb->getQuery()->getArrayResult();

        $grouped = [];
        foreach ($rows as $row) {
            $translated = $row['translated'] ?? '';
            if ($translated === '' || empty($row['code'])) {
                continue;
            }
            $grouped[$row['original']][$row['code']] = $translated;
        }

        return $grouped;
    }

    /**
     * Fetch all available languages.
     * @return Language[]
     */
    public function getAllLanguages(): array
    {
        return $this->entityManager->getRepository(Language::class)->findAll();
    }

    public function getLanguageByCode(string $code): ?Language
    {
        return $this->entityManager->getRepository(Language::class)->findOneBy(['code' => $code]);
    }

    /**
     * Check if a translation record exists for a given string and language.
     */
    public function findTranslation(string $originalString, int $languageId): ?Translation
    {
        return $this->entityManager->getRepository(Translation::class)->findOneBy([
            'originalString' => $originalString,
            'language' => $languageId,
        ]);
    }

    /**
     * Create a new translation record directly (for auto-collection).
     */
    public function createTranslation(string $originalString, Language $language): void
    {
        $translation = new Translation();
        $translation->originalString = $originalString;
        $translation->translatedString = '';
        $translation->language = $language;

        $this->entityManager->persist($translation);
        $this->entityManager->flush();
        $this->entityManager->detach($translation);
    }
}
