<?php
namespace Skeletor\User\Validator;

use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Core\Validator\ValidatorInterface;
use Skeletor\User\Repository\UserRepository;
use Skeletor\Core\Security\Csrf;

/**
 * Class Login.
 * Login validator.
 *
 * @package Skeletor\User\Validator
 */
class Login implements ValidatorInterface
{
    private $messages = [];

    /**
     * Login constructor.
     * @param UserRepository $userRepo
     * @param Csrf $csrf
     */
    public function __construct(private Csrf $csrf)
    {    }

    /**
     * Validates provided data, and sets errors with Flash in session.
     *
     * @param $data
     *
     * @return bool
     */
    public function isValid(array $data): bool
    {
        if (!$this->csrf->validate($data)) {
            throw new InvalidFormTokenException();
        }
        $valid = true;
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->messages['email'][] = 'Email you entered is not valid.';
            $valid = false;
        }

        return $valid;
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
