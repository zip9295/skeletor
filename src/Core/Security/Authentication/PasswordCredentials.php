<?php
declare(strict_types=1);

namespace Skeletor\Core\Security\Authentication;

/**
 * Password-based authentication credentials
 */
class PasswordCredentials implements CredentialsInterface
{
    public function __construct(
        private string $email,
        private string $password,
        private string $entityType = 'user',
        private bool $rememberMe = false
    ) {}

    public function getType(): string
    {
        return 'password';
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function shouldRememberMe(): bool
    {
        return $this->rememberMe;
    }

    public function isValid(): bool
    {
        return !empty($this->email)
            && !empty($this->password)
            && filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
