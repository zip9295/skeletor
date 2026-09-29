<?php
declare(strict_types=1);

namespace Skeletor\Core\Security\Authentication;

/**
 * Credentials for a single-sign-on handshake.
 *
 * Named for what it is rather than for Google and Facebook: the same OAuth2 round trip backs
 * a corporate identity provider as backs a consumer social login, and the apps that need this
 * next are the former.
 */
class SsoCredentials implements CredentialsInterface
{
    public function __construct(
        private string $provider,
        private string $code,
        private string $state,
        private string $entityType = 'user'
    ) {}

    public function getType(): string
    {
        return 'sso';
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function isValid(): bool
    {
        return !empty($this->provider)
            && !empty($this->code)
            && !empty($this->state);
    }
}
