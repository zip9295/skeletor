<?php

namespace Skeletor\ThemeSettings\SocialLinks\Validator;

use Skeletor\Core\Validator\ValidatorInterface;
use Skeletor\Core\Security\Csrf;

class SocialLinks implements ValidatorInterface
{
    /**
     * @var Csrf
     */
    private $csrf;

    private $messages = [];

    /**
     * @param Csrf $csrf
     */
    public function __construct(Csrf $csrf)
    {
        $this->csrf = $csrf;
    }

    /**
     * Validates provided data, and sets errors with Flash in session.
     *
     * @param $data
     *
     * @return bool
     */
    public function isValid(array $data): bool
    {
        //@todo: Implement CSRF token validation
//        if (!$this->csrf->validate($data)) {
//            throw new InvalidFormTokenException();
//        }
        $valid = true;
        return $valid;
    }

    /**
     * Hack used for testing
     *
     * @return string
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}