<?php

declare(strict_types=1);

namespace Skeletor\Tests\Support;

use Skeletor\Core\Security\Authentication\AuthenticatableInterface;
use Skeletor\Core\Security\Authentication\TwoFactorAwareInterface;

/**
 * A stand-in for whatever an app calls its account.
 *
 * Deliberately not a Doctrine entity and not a User: the point of the authenticatable
 * abstraction is that the framework never needs to know which of them it is holding, and a
 * test double that is neither proves that better than one which is one of them.
 */
class TestAccount implements AuthenticatableInterface, TwoFactorAwareInterface
{
    public ?string $ipv4 = null;

    public ?\DateTime $lastLogin = null;

    public function __construct(
        private int|string $id = 1,
        private string $email = 'someone@example.com',
        private ?string $password = null,
        private int $role = 1,
        private bool $active = true,
        private string $redirectPath = '/dashboard/',
        private ?string $displayName = 'Test Account',
        // True by default. TestAccount implements TwoFactorAwareInterface, and that
        // interface is an opt-OUT: false here made every account silently decline the
        // second factor, which is not what an ordinary account does. Entities that do not
        // implement the interface at all are required whenever the app switch is on, and
        // this default matches them.
        private bool $twoFactorRequired = true,
        private array $supportedAuthenticators = ['password', 'magic_link'],
    ) {}

    public static function withPassword(string $plain, int|string $id = 1, string $email = 'someone@example.com'): self
    {
        return new self($id, $email, password_hash($plain, PASSWORD_BCRYPT));
    }

    public function getAuthIdentifier(): string
    {
        return $this->email;
    }

    public function getAuthPassword(): ?string
    {
        return $this->password;
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
        return $this->active;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function updateLoginInfo(string $ip, \DateTime $time): void
    {
        $this->ipv4 = $ip;
        $this->lastLogin = $time;
    }

    public function supportsAuthenticator(string $authenticatorType): bool
    {
        return in_array($authenticatorType, $this->supportedAuthenticators, true);
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function getFirstName(): ?string
    {
        return 'Test';
    }

    public function getLastName(): ?string
    {
        return 'Account';
    }

    public function requiresTwoFactor(): bool
    {
        return $this->twoFactorRequired;
    }

    public function requireTwoFactor(bool $required = true): self
    {
        $this->twoFactorRequired = $required;

        return $this;
    }
}
