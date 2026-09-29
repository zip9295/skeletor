<?php
namespace Skeletor\Core\Login\Validator;

use Skeletor\Core\Login\Repository\ForgotPasswordRepository;
use Skeletor\Core\Security\Csrf;

class ResetPasswordStrict implements ResetPasswordInterface
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
        $password = $data['password'];
        if (strlen($password) < 10) {
            $this->messages['password'][] = 'Password must be at least 10 characters long.';
            $valid = false;
        }
        if (!preg_match('@[A-Z]@', $password)) {
            $this->messages['password'][] = 'Password must contain at least one uppercase letter.';
            $valid = false;
        }
        if (!preg_match('@[a-z]@', $password)) {
            $this->messages['password'][] = 'Password must contain at least one lowercase letter.';
            $valid = false;
        }
        if (!preg_match('@[0-9]@', $password)) {
            $this->messages['password'][] = 'Password must contain at least one number.';
            $valid = false;
        }
        if (!preg_match('@[^\w]@', $password)) {
            $this->messages['password'][] = 'Password must contain at least one special character (!@#$%^&* etc).';
            $valid = false;
        }
        // Reject common weak passwords even if they pass the rules above
        $common = ['Password1!', 'Qwerty1234!', 'Passw0rd1!'];
        if (in_array($password, $common, true)) {
            $this->messages['password'][] = 'This password is too common. Please choose a more unique password.';
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