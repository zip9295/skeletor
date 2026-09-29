<?php
declare(strict_types=1);

namespace Skeletor\Core\Security\Authenticator;

use Skeletor\Core\Security\Authentication\AuthenticatableInterface;
use Skeletor\Core\Security\AuthPolicy;
use Skeletor\Core\Security\EntityRegistry;
use Skeletor\Core\Login\Exception\AuthMethodDisabled;

/**
 * Base authenticator with common functionality
 */
abstract class AbstractAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        protected EntityRegistry $entityRegistry,
        protected AuthPolicy $policy
    ) {}

    /**
     * Get repository for the entity type
     */
    protected function getRepository(string $entityType)
    {
        return $this->entityRegistry->getRepository($entityType);
    }

    /**
     * Refuse a method this account cannot use, once the account is known.
     *
     * The registry has already checked that the application offers this method; this is the
     * account's own answer, via supportsAuthenticator(). It can only narrow — an entity
     * cannot switch on a method config has switched off — so this is safe to call after the
     * entity is loaded but before any session is established.
     *
     * @throws AuthMethodDisabled
     */
    protected function assertAvailableTo(string $method, AuthenticatableInterface $entity): void
    {
        if (!$this->policy->availableTo($method, $entity)) {
            throw new AuthMethodDisabled(sprintf(
                'This account cannot authenticate with "%s".',
                $method
            ));
        }
    }

    /**
     * Update login info after successful authentication
     */
    protected function updateLoginInfo(AuthenticatableInterface $entity): void
    {
        $ip = ip2long($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $entity->updateLoginInfo((string) $ip, new \DateTime());

        $repository = $this->getRepository($this->entityRegistry->getTypeForEntity($entity));
        $repository->updateLoginInfo($entity);
    }
}
