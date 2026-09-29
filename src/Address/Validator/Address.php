<?php
namespace Skeletor\Address\Validator;

use Skeletor\Core\Validator\ValidatorInterface;
use Skeletor\Core\Security\Csrf;

class Address implements ValidatorInterface
{

    /**
     * @var Csrf
     */
    private $csrf;

    private $messages = [];

    /**
     * User constructor.
     *
     * @param Flash $flash
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
        $valid = true;
        if ($data['address'] === '') {
            $this->messages['address'][] = 'You must enter street field.';
            $valid = false;
        }
        if ($data['zip'] === '') {
            $this->messages['zip'][] = 'You must enter zip field.';
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