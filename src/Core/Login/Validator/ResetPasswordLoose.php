<?php
namespace Skeletor\Core\Login\Validator;

use Skeletor\Core\Login\Repository\ForgotPasswordRepository;
use Skeletor\Core\Security\Csrf;

class ResetPasswordLoose implements ResetPasswordInterface
{
    /**
     * @var UserRepository
     */
    protected $userRepo;

    /**
     * @var Csrf
     */
    private $csrf;

    private $messages = [];

    /**
     * User constructor.
     *
     * @param UserRepository $userRepo
     */
    public function __construct(ForgotPasswordRepository $userRepo, Csrf $csrf)
    {
        $this->userRepo = $userRepo;
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
        $valid = true;

        if ($data['password'] !== $data['password2']) {
            $this->messages['password'][] = 'Passwords you entered do not match.';
            $valid = false;
        }
        if(strlen($data['password']) < 10) {
            $this->messages['password'][] = 'Password should be at least 10 characters.';
            $valid = false;
        }

        if (!$this->csrf->validate($data)) {
            $this->messages['general'][] = 'Invalid form key.';
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