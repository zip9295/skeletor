<?php

namespace Skeletor\Attribute\Validator;

use Skeletor\Core\Validator\ValidatorInterface;
use Skeletor\Core\Security\Csrf;

class Attribute implements ValidatorInterface
{

    private array $messages = [];

    public function __construct(private Csrf $csrf) {}

    public function isValid(array $data): bool
    {
        $valid = true;
        // @todo add validation
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