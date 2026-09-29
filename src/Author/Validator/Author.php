<?php

namespace Skeletor\Author\Validator;

use Skeletor\Core\Security\Csrf;
use Skeletor\Core\Validator\ValidatorInterface;

class Author implements ValidatorInterface
{
    private array $messages = [];

    public function __construct(private Csrf $csrf) {}

    public function isValid(array $data): bool
    {
        $valid = true;

        if (($data['firstName'] ?? '') === '') {
            $this->messages['firstName'][] = 'First name is required.';
            $valid = false;
        }
        if (strlen((string) ($data['firstName'] ?? '')) > 128) {
            $this->messages['firstName'][] = 'First name is too long.';
            $valid = false;
        }
        if (($data['lastName'] ?? '') === '') {
            $this->messages['lastName'][] = 'Last name is required.';
            $valid = false;
        }
        if (strlen((string) ($data['lastName'] ?? '')) > 128) {
            $this->messages['lastName'][] = 'Last name is too long.';
            $valid = false;
        }
        if (strlen((string) ($data['displayName'] ?? '')) > 128) {
            $this->messages['displayName'][] = 'Display name is too long.';
            $valid = false;
        }

        // seoTitle/seoDescription come from the Seo trait and are NOT NULL columns, so they are
        // required here for the same reason they are on Page and Post.
        if (($data['seoTitle'] ?? '') === '') {
            $this->messages['seoTitle'][] = 'SEO title is required.';
            $valid = false;
        }
        if (strlen((string) ($data['seoTitle'] ?? '')) > 128) {
            $this->messages['seoTitle'][] = 'SEO title is too long.';
            $valid = false;
        }
        if (($data['seoDescription'] ?? '') === '') {
            $this->messages['seoDescription'][] = 'SEO description is required.';
            $valid = false;
        }
        if (strlen((string) ($data['seoDescription'] ?? '')) > 255) {
            $this->messages['seoDescription'][] = 'SEO description is too long.';
            $valid = false;
        }

        if (!$this->csrf->validate($data)) {
            $this->messages['general'][] = 'Form key is invalid.';
            $valid = false;
        }

        return $valid;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
