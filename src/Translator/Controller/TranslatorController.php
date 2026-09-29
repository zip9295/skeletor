<?php

namespace Skeletor\Translator\Controller;

use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use League\Plates\Engine;
use Skeletor\Core\Controller\AjaxCrudController;
use Skeletor\Translator\Service\Translator;
use Skeletor\Translator\Service\TranslatorCrudService;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

class TranslatorController extends AjaxCrudController
{
    const TITLE_VIEW = "View translations";
    const TITLE_CREATE = "Create new translation";
    const TITLE_UPDATE = "Edit translation: ";
    const TITLE_UPDATE_SUCCESS = "Translation updated successfully.";
    const TITLE_CREATE_SUCCESS = "Translation created successfully.";
    const TITLE_DELETE_SUCCESS = "Translation deleted successfully.";
    const PATH = 'translator';

    protected $tableViewConfig = ['createButton' => false];

    private array $allowedColumnsForUpdate = ['originalString', 'translatedString', 'language'];

    private array $availableLanguages = [];

    public function __construct(
        TranslatorCrudService $service,
        Session $session,
        Config $config,
        Flash $flash,
        Engine $template, Logger $logger,
        private Translator $translator
    ) {
        parent::__construct($service, $session, $config, $flash, $template, $logger);
        $this->availableLanguages = $this->translator->getAvailableLanguages();
    }

    public function save(): \Psr\Http\Message\ResponseInterface
    {
        $data = json_decode($this->getRequest()->getBody()->getContents(), true);
        if(!$this->csrf()->validate($data)) {
            return $this->jsonResponse([
                'status' => false,
                'token' => $this->csrf()->getHiddenInputString(),
                'message' => $this->translate('Access denied. Please refresh the page and try again.')
            ], 400);
        }
        if (
            !isset($data['column'], $data['value'], $data['id'])
            || !in_array($data['column'], $this->allowedColumnsForUpdate)
            || ($data['column'] === 'language' && !$this->isLanguageAllowed((int)$data['value']))
        ) {
            return $this->jsonResponse([
                'status' => false,
                'token' => $this->csrf()->getHiddenInputString(),
                'message' => $this->translate('Invalid data.')
            ], 400);
        }
        try {
            $value = $data['value'];
            if ($data['column'] === 'language') {
                $value = (int)$data['value'];
            }
            $this->service->updateField($data['column'], $value, $data['id']);
        } catch (\Throwable $e) {
            return $this->jsonResponse(
                [
                    'status' => false,
                    'token' => $this->csrf()->getHiddenInputString(),
                    'message' => $this->translate('Failed to update translation.')
                ],
                500
            );
        }
        return $this->jsonResponse(
            [
                'status' => true,
                'token' => $this->csrf()->getHiddenInputString(),
                'message' => $this->translate('Translation updated successfully.')
            ]
        );
    }

    public function switchLanguage(): \Psr\Http\Message\ResponseInterface
    {
        $data = $this->getRequest()->getParsedBody();
        $languageCode = $data['language'] ?? null;

        if (!$languageCode) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Language code is required.'], 400);
        }

        // Validate language exists
        $language = $this->service->getRepository()->getLanguageByCode($languageCode);
        if (!$language) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid language.'], 400);
        }

        // Store in session
        $this->getSession()->getStorage()->offsetSet('selectedLanguage', $languageCode);

        return $this->jsonResponse(['status' => 'success', 'language' => $languageCode]);
    }

    public function createTranslationsForLanguage(int $targetLanguageId, int $sourceLanguageId = 1): void
    {
        $this->service->createTranslationsForLanguage($targetLanguageId, $sourceLanguageId);
    }

    public function isLanguageAllowed(int $languageId): bool
    {
        return array_any($this->availableLanguages, fn($language) => $language->id === $languageId);
    }

    private function jsonResponse(array $data, int $status = 200): \Psr\Http\Message\ResponseInterface
    {
        $this->getResponse()->getBody()->write(json_encode($data));
        return $this->getResponse()->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
