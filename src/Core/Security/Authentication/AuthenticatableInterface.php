<?php
declare(strict_types=1);

namespace Skeletor\Core\Security\Authentication;

/**
 * Interface for entities that can be authenticated.
 * Any entity implementing this interface can be used for login.
 */
interface AuthenticatableInterface
{
    /**
     * Get unique identifier for authentication (usually email)
     */
    public function getAuthIdentifier(): string;

    /**
     * Get hashed password (null for magic link only entities)
     */
    public function getAuthPassword(): ?string;

    /**
     * Get user role for authorization
     */
    public function getAuthRole(): int;

    /**
     * Get redirect path after successful login
     */
    public function getRedirectPath(): string;

    /**
     * Check if entity is active and can login
     */
    public function isActive(): bool;

    /**
     * Update login metadata (IP, timestamp)
     */
    public function updateLoginInfo(string $ip, \DateTime $time): void;

    /**
     * Check if entity supports specific authenticator type
     */
    public function supportsAuthenticator(string $authenticatorType): bool;

    /**
     * Get entity ID
     */
    public function getId(): int|string;

    /**
     * Get entity email
     */
    public function getEmail(): string;

    /**
     * Get entity display name
     */
    public function getDisplayName(): ?string;

    /**
     * Get entity last name
     */
//    public function getLastName(): ?string;
}
