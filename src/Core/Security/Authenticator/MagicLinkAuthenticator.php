<?php
declare(strict_types=1);

namespace Skeletor\Core\Security\Authenticator;

use Skeletor\Core\Security\Authentication\AuthenticatableInterface;
use Skeletor\Core\Security\Authentication\CredentialsInterface;
use Skeletor\Core\Security\Authentication\MagicLinkCredentials;
use Skeletor\Core\Security\AuthPolicy;
use Skeletor\Core\Security\EntityRegistry;
use Skeletor\Core\Login\Exception\InvalidCredentials;
use Skeletor\Core\Login\Repository\MagicLinkTokenRepository;

/**
 * Magic link authenticator
 * Authenticates users via email link with secure token
 */
class MagicLinkAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        EntityRegistry $entityRegistry,
        AuthPolicy $policy,
        private MagicLinkTokenRepository $tokenRepository
    ) {
        parent::__construct($entityRegistry, $policy);
    }

    public function handles(): string
    {
        return AuthPolicy::METHOD_MAGIC_LINK;
    }

    public function supports(CredentialsInterface $credentials): bool
    {
        return $credentials instanceof MagicLinkCredentials;
    }

    public function authenticate(CredentialsInterface $credentials): AuthenticatableInterface
    {
        if (!$credentials instanceof MagicLinkCredentials) {
            throw new \InvalidArgumentException('MagicLinkAuthenticator requires MagicLinkCredentials');
        }

        // Verify token and get user data
        $tokenData = $this->tokenRepository->verifyToken($credentials->getToken());

        if ($tokenData['entityType'] !== $credentials->getEntityType()) {
            throw new InvalidCredentials('Invalid token for this entity type');
        }

        // Get the entity
        $repository = $this->getRepository($credentials->getEntityType());
        $entity = $repository->getById($tokenData['entityId']);

        if (!$entity instanceof AuthenticatableInterface) {
            throw new \RuntimeException('Entity must implement AuthenticatableInterface');
        }

        $this->assertAvailableTo(AuthPolicy::METHOD_MAGIC_LINK, $entity);

        if (!$entity->isActive()) {
            throw new InvalidCredentials('Account is not active');
        }

        // Invalidate the token (one-time use)
        $this->tokenRepository->invalidateToken($tokenData['tokenId']);

        $this->updateLoginInfo($entity);

        return $entity;
    }
}
