<?php
declare(strict_types=1);

namespace Skeletor\Core\Security\Authentication;

/**
 * Default implementation of AuthenticatableInterface
 * Provides common functionality for authenticatable entities
 */
trait AuthenticatableTrait
{
    protected string $redirectPath = '/';

    public function getAuthIdentifier(): string
    {
        return $this->email;
    }

    public function getAuthPassword(): ?string
    {
        return $this->password ?? null;
    }

    public function getAuthRole(): int
    {
        return $this->role;
    }

    public function getRedirectPath(): string
    {
        return $this->redirectPath;
    }

    public function isActive(): bool
    {
        return (bool) $this->isActive;
    }

    public function updateLoginInfo(string $ip, \DateTime $time): void
    {
        $this->ipv4 = $ip;
        $this->lastLogin = $time;
    }

    public function supportsAuthenticator(string $authenticatorType): bool
    {
        // By default, support password and magic link authentication
        return in_array($authenticatorType, ['password', 'magic_link']);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName ?? null;
    }

    public function getLastName(): ?string
    {
        return $this->lastName ?? null;
    }
}
