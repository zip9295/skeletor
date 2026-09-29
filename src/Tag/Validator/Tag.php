<?php
namespace Skeletor\Tag\Validator;

use Skeletor\Core\Validator\ValidatorInterface;
use Skeletor\Core\Security\Csrf;

/**
 * Class Supplier.
 * Supplier validator.
 *
 * @package Fakture\Client\Validator
 */
class Tag implements ValidatorInterface
{

    /**
     * @var Csrf
     */
    private $csrf;

    private $messages = [];

    /**
     * User constructor.
     *
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
        $valid = true;
        if (strlen($data['title']) < 3) {
            $this->messages['title'][] = 'Title must be at least 3 characters long.';
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
