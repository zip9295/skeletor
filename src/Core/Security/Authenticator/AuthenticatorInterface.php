<?php
declare(strict_types=1);

namespace Skeletor\Core\Security\Authenticator;

use Skeletor\Core\Security\Authentication\AuthenticatableInterface;
use Skeletor\Core\Security\Authentication\CredentialsInterface;

/**
 * Interface for authentication mechanisms
 */
interface AuthenticatorInterface
{
    /**
     * The login method this authenticator implements — one of the AuthPolicy::METHOD_*
     * strings, and the same value the matching credentials report from getType().
     *
     * Declared so the registry can check at construction that every method the application
     * has switched on actually has something behind it, rather than discovering the gap when
     * somebody tries to log in.
     */
    public function handles(): string;

    /**
     * Check if this authenticator supports the given credentials
     */
    public function supports(CredentialsInterface $credentials): bool;

    /**
     * Authenticate using the provided credentials
     *
     * @throws \Skeletor\Core\Login\Exception\InvalidCredentials
     * @throws \Skeletor\Core\Login\Exception\AuthMethodDisabled
     * @throws \Skeletor\Core\Mapper\NotFoundException
     */
    public function authenticate(CredentialsInterface $credentials): AuthenticatableInterface;
}
