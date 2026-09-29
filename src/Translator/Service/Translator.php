<?php

namespace Skeletor\Translator\Service;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use Psr\Log\LoggerInterface;
use Skeletor\Translator\Entity\Language;
use Skeletor\Translator\Repository\TranslationRepository;

class Translator implements ExtensionInterface
{
    const CACHE_KEY = 'translations';
    const CACHE_TTL = 86400; // 24 hours
    const LANGUAGE_ENGLISH = 'en-us';

    private ?Language $lang = null;
    private array $translations = [];

    public function __construct(
        private TranslationRepository $repository,
        private \Redis $redis,
        private ?LoggerInterface $logger = null,
        private string $cachePrefix = 'skeletor',
    ) {
    }

    public function __invoke(string $languageCode): void
    {
        $this->setLanguage($languageCode);
    }

    public function setLanguage(string $languageCode): void
    {
        // Try exact match first
        $this->lang = $this->repository->getLanguageByCode($languageCode);

        if ($this->lang) {
            $this->loadTranslations();
        }
    }

    public function checkLanguage(): void
    {
        if (!$this->lang) {
            $this->setLanguage(static::LANGUAGE_ENGLISH);
        }
    }

    public function register(Engine $engine): void
    {
        $this->checkLanguage();
        $engine->registerFunction('t', [$this, 'translate']);
    }

    public function translate(string $originalString): string
    {
        if ($this->lang === null) {
            return $originalString;
        }

        $isBaseLang = str_starts_with($this->lang->code, static::LANGUAGE_ENGLISH);

        // Auto-collect unknown strings for all languages (builds the dictionary)
        if (!isset($this->translations[$originalString])) {
            $this->collectTranslation($originalString);
            $this->translations[$originalString] = '';

            return $originalString;
        }

        // English is the base language — always return the original string
        if ($isBaseLang) {
            return $originalString;
        }

        // Not yet translated — return original as fallback
        if ($this->translations[$originalString] === '' || $this->translations[$originalString] === null) {
            return $originalString;
        }

        return $this->translations[$originalString];
    }

    // @TODO Ship a framework-level CLI action (e.g. Skeletor\Translator\Action\ResetTranslationsCache
    //       invoked via a default cliMap entry `resetTranslationsCache`) that loops
    //       getAvailableLanguages() and calls resetCache() on each. Nothing invalidates the
    //       per-language cache after a direct `translation` table edit/import, so every project
    //       currently re-implements this. A reference implementation lives in the Solidarity app:
    //       Solidarity\Backend\Action\ResetTranslationsCache — generalise it and move it here.
    public function resetCache(): void
    {
        if ($this->lang) {
            $this->redis->del($this->getCacheKey());
        }
    }

    public function getLanguage(): ?Language
    {
        return $this->lang;
    }

    /**
     * @return Language[]
     */
    public function getAvailableLanguages(): array
    {
        return $this->repository->getAllLanguages();
    }

    private function getCacheKey(): string
    {
        return $this->cachePrefix . static::CACHE_KEY . '#' . $this->lang->getId() . '#';
    }

    private function loadTranslations(): void
    {
        $key = $this->getCacheKey();
        $cached = $this->redis->get($key);

        if ($cached !== false) {
            $this->translations = json_decode($cached, true);
            return;
        }

        $this->translations = $this->repository->getTranslations($this->lang->getId());
        $this->redis->set($key, json_encode($this->translations), static::CACHE_TTL);
    }

    private function collectTranslation(string $string): void
    {
        try {
            $existing = $this->repository->findTranslation($string, $this->lang->getId());
            if ($existing) {
                return;
            }

            $this->repository->createTranslation($string, $this->lang);
        } catch (UniqueConstraintViolationException) {
            // Race condition — another request already created it. Safe to ignore.
        } catch (\Throwable $e) {
            $this->logger?->warning('Failed to collect translation', [
                'string' => $string,
                'languageId' => $this->lang->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
