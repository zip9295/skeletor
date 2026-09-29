<?php

namespace Skeletor\Reference\Validator;

use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Core\Validator\ValidatorInterface;
use Skeletor\Core\Security\Csrf;

class Reference implements ValidatorInterface
{
    private array $messages = [];

    public function __construct(private Csrf $csrf)
    {
    }

    public function isValid(array $data): bool
    {
        $valid = true;
        if (!$this->csrf->validate($data)) {
            throw new InvalidFormTokenException();
        }

        if(trim($data['title']) === '') {
            $this->messages['title'][] = 'Title is required.';
            $valid = false;
        }
        if(trim($data['content']) === '') {
            $this->messages['content'][] = 'Content is required.';
            $valid = false;
        }
//        if(trim($data['url']) === '') {
//            $this->messages['url'][] = 'Url is required.';
//            $valid = false;
//        }

        return $valid;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}