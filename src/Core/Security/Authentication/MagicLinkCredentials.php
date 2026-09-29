<?php
declare(strict_types=1);

namespace Skeletor\Core\Security\Authentication;

/**
 * Magic link authentication credentials
 */
class MagicLinkCredentials implements CredentialsInterface
{
    public function __construct(
        private string $token,
        private string $entityType = 'user'
    ) {}

    public function getType(): string
    {
        return 'magic_link';
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function isValid(): bool
    {
        return !empty($this->token) && strlen($this->token) >= 32;
    }
}
