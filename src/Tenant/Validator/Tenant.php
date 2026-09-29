<?php

namespace Skeletor\Tenant\Validator;

use Skeletor\Core\Validator\ValidatorInterface;
use Skeletor\Core\Security\Csrf;

/**
 * Class Tenant.
 * User validator.
 *
 * @package Skeletor\Tenant\Validator
 */
class Tenant implements ValidatorInterface
{

    private $messages = [];

    /**
     * User constructor.
     *
     * @param Csrf $csrf
     */
    public function __construct()
    {
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
        $emailValidator = new \Laminas\Validator\EmailAddress();
        $valid = true;

        if (strlen($data['name']) < 3) {
            $this->messages['name'][] = 'Name must be at least 3 characters long.';
            $valid = false;
        }
        if (isset($data['email']) && (strlen($data['email']) > 0 && !$emailValidator->isValid($data['email']))
            || (strlen($data['email']) < 8)) {
            $this->messages['email'][] = 'Email you entered is not valid or too short.';
            $valid = false;
        }

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
