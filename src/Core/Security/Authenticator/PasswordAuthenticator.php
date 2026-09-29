<?php
declare(strict_types=1);

namespace Skeletor\Core\Security\Authenticator;

use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Core\Security\Authentication\AuthenticatableInterface;
use Skeletor\Core\Security\Authentication\CredentialsInterface;
use Skeletor\Core\Security\Authentication\PasswordCredentials;
use Skeletor\Core\Security\AuthPolicy;
use Skeletor\Core\Login\Exception\InvalidCredentials;

/**
 * Password-based authenticator
 * Migrated from DbProvider
 */
class PasswordAuthenticator extends AbstractAuthenticator
{
    /**
     * A bcrypt hash of nothing in particular, verified against when no account was found.
     *
     * password_verify() is deliberately slow, so skipping it for an unknown address makes a
     * failed login measurably faster than a wrong password — which turns this endpoint into
     * a way to enumerate who has an account. Hashing anyway costs one comparison per failed
     * login and removes the signal.
     */
    private const TIMING_DECOY = '$2y$10$usesomesillystringfoeueoueoueoueoueoueoueoueoueoueoueoueoueu';

    public function handles(): string
    {
        return AuthPolicy::METHOD_PASSWORD;
    }

    public function supports(CredentialsInterface $credentials): bool
    {
        return $credentials instanceof PasswordCredentials;
    }

    public function authenticate(CredentialsInterface $credentials): AuthenticatableInterface
    {
        if (!$credentials instanceof PasswordCredentials) {
            throw new \InvalidArgumentException('PasswordAuthenticator requires PasswordCredentials');
        }

        if ($credentials->getEmail() === '' || $credentials->getPassword() === '') {
            throw new InvalidCredentials('Email and password are both required');
        }

        // Goes through the registry rather than the repository so a missing account is null
        // whichever convention the repository follows — some throw, some return null.
        $entity = $this->entityRegistry->findByEmail($credentials->getEntityType(), $credentials->getEmail());

        if (!$entity) {
            password_verify($credentials->getPassword(), self::TIMING_DECOY);

            throw new NotFoundException('Email not found in system');
        }

        $this->assertAvailableTo(AuthPolicy::METHOD_PASSWORD, $entity);

        $password = $entity->getAuthPassword();
        if ($password === null || $password === '') {
            password_verify($credentials->getPassword(), self::TIMING_DECOY);

            throw new InvalidCredentials('Password authentication not supported for this account');
        }

        if (!password_verify($credentials->getPassword(), $password)) {
            throw new InvalidCredentials('Invalid password');
        }

        // Checked after the password, on purpose: answering "this account is disabled" to
        // someone who has not proved they own it tells them the address is real.
        if (!$entity->isActive()) {
            throw new InvalidCredentials('Account is not active');
        }

        $this->updateLoginInfo($entity);

        return $entity;
    }
}
