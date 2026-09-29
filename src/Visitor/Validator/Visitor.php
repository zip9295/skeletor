<?php
namespace Skeletor\Visitor\Validator;

use Skeletor\Core\Validator\ValidatorInterface;
use Skeletor\User\Service\Session;
use Skeletor\Visitor\Repository\VisitorRepository;
use Skeletor\Core\Security\Csrf;

/**
 * Class Visitor.
 * Visitor validator.
 *
 * @package Skeletor\Visitor\Validator
 */
class Visitor implements ValidatorInterface
{
    /**
     * @var VisitorRepository
     */
    protected $repo;

    /**
     * @var Csrf
     */
    private $csrf;

    private $messages = [];

    /**
     * User constructor.
     *
     * @param Flash $flash
     * @param repository $repo
     */
    public function __construct(VisitorRepository $repo, Csrf $csrf, private Session $userSession,
                                private \Skeletor\User\Service\User $user)
    {
        $this->repo = $repo;
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
        $emailValidator = new \Laminas\Validator\EmailAddress();
        $valid = true;
        $isFromAdmin = false;
        $userId = $this->userSession->getLoggedInUserId();
        if($userId) {
            try {
                $user = $this->user->getById($userId);
                if ($user) {
                    $isFromAdmin = true;
                }
            } catch(\Exception) {
                //
            }
        }
        if (!$emailValidator->isValid($data['email'])) {
            $this->messages['email'][] = 'Email you entered is not valid.';
            $valid = false;
        }

        if ((int) $data['id'] === 0 && $this->repo->emailExists($data['email'])) {
            $this->messages['email'][] = 'Email you entered already exists in system.';
            $valid = false;
        }
        // Only for visitor dashboard changes
        if(!isset($data['registerForm']) && trim($data['password']) !== '' && trim($data['password2']) !== '') {
            if(!$isFromAdmin && trim($data['currentPassword']) === '') {
                $this->messages['password'][] = 'The current password is required when changing your password.';
                $valid = false;
            } else {
                if(isset($data['id']) && trim($data['id']) !== '') {
                    $visitor = $this->repo->getById($data['id']);
                    if ($visitor) {
                        if (!$isFromAdmin && !password_verify($data['currentPassword'], $visitor->getPassword())) {
                            $this->messages['password'][] = 'The current password is incorrect';
                            $valid = false;
                        }
                    } else {
                        $this->messages['general'][] = 'An unexpected error occurred.';
                        $valid = false;
                    }
                }
            }
        }

        if ($data['password'] !== $data['password2']) {
            $this->messages['password'][] = 'Passwords you entered do not match.';
            $valid = false;
        }

        if ((int) $data['id'] === 0 && strlen($data['password']) < 6) {
            $this->messages['password'][] = 'Password must be at least 6 characters long.';
            $valid = false;
        }
        if (strlen($data['firstName']) < 3) {
            $this->messages['password'][] = 'First name must be at least 3 characters long.';
            $valid = false;
        }
        if ((int) $data['role'] === 0) {
            $this->messages['role'][] = 'Invalid role selected.';
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
