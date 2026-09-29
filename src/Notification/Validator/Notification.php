<?php

namespace Skeletor\Notification\Validator;

use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Core\Validator\ValidatorInterface;
use Skeletor\Core\Security\Csrf;

class Notification implements ValidatorInterface
{
    private array $messages = [];

    public function __construct(private Csrf $csrf) {}

    public function isValid(array $data, bool $useCSRF = true): bool
    {
        if (!$this->csrf->validate($data)) {
            throw new InvalidFormTokenException();
        }
        $valid = true;
        return $valid;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}