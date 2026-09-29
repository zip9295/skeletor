<?php
namespace Skeletor\Core\Login\Validator;

use Skeletor\Core\Login\Repository\ForgotPasswordRepository;
use Skeletor\Core\Security\Csrf;

class ForgotPassword
{
    /**
     * @var ForgotPasswordRepository
     */
    protected $fpRepo;

    /**
     * @var Csrf
     */
    private $csrf;

    private $messages = [];

    /**
     * User constructor.
     *
     * @param ForgotPasswordRepository $fpRepo
     */
    public function __construct(ForgotPasswordRepository $fpRepo, Csrf $csrf)
    {
        $this->fpRepo = $fpRepo;
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
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->messages['email'][] = 'Email you entered is not valid.';
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