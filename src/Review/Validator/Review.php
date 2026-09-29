<?php

namespace Skeletor\Review\Validator;

use Skeletor\Core\Validator\ValidatorInterface;
use Skeletor\Core\Security\Csrf;

class Review implements ValidatorInterface
{

    private array $messages = [];

    public function __construct(private Csrf $csrf) {}

    public function isValid(array $data, bool $useCSRF = true): bool
    {
        $valid = true;
        if(!isset($data['body'])) {
            $data['body'] = '';
        }
        if ($useCSRF && !$this->csrf->validate($data)) {
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