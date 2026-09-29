<?php
declare(strict_types=1);

namespace Skeletor\Core\Security\Authentication;

/**
 * Base interface for authentication credentials
 */
interface CredentialsInterface
{
    /**
     * Get the type of credentials (password, magic_link, sso)
     */
    public function getType(): string;

    /**
     * Get entity type being authenticated (user, delegate, educator, etc.)
     */
    public function getEntityType(): string;

    /**
     * Validate that credentials are properly formed
     */
    public function isValid(): bool;
}
