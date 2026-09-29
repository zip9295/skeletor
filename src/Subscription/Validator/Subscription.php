<?php

namespace Skeletor\Subscription\Validator;

use Skeletor\Core\Validator\ValidatorInterface;
use Skeletor\Core\Security\Csrf;

class Subscription implements ValidatorInterface
{

    private array $messages = [];
    public function __construct(private Csrf $csrf) {}

    public function isValid(array $data): bool
    {
        $valid = true;
        if (strlen($data['title']) < 3 || strlen($data['title']) > 255) {
            $this->messages['title'][] = 'Title must be between 3 and 255 characters long.';
            $valid = false;
        }
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