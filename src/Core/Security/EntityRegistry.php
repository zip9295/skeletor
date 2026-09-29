<?php
declare(strict_types=1);

namespace Skeletor\Core\Security;

use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Core\Security\Authentication\AuthenticatableInterface;
use Skeletor\Core\Login\Repository\LoginRepositoryInterface;

/**
 * Registry for authenticatable entities
 * Maps entity types to their classes and repositories
 */
class EntityRegistry
{
    /** @var array<string, array{class: string, repository: LoginRepositoryInterface}> */
    private array $entities = [];

    /**
     * Register an authenticatable entity type
     */
    public function register(string $type, string $className, LoginRepositoryInterface $repository): void
    {
        $this->entities[$type] = [
            'class' => $className,
            'repository' => $repository,
        ];
    }

    /**
     * Get repository for entity type
     */
    public function getRepository(string $type): LoginRepositoryInterface
    {
        if (!isset($this->entities[$type])) {
            throw new \InvalidArgumentException(sprintf('Unknown entity type: %s', $type));
        }

        return $this->entities[$type]['repository'];
    }

    /**
     * Look up an entity by email, or null when there is no such account.
     *
     * Exists because the repositories disagree: UserRepository and DelegateRepository throw
     * NotFoundException for an unknown address, DonorRepository returns null. Framework code
     * that has to work across every registered type cannot pick one convention, and the
     * asymmetry has already produced "if (!$entity)" guards that never fire. Everything in
     * the framework goes through here; the repositories keep their own behaviour.
     */
    public function findByEmail(string $type, string $email): ?AuthenticatableInterface
    {
        try {
            $entity = $this->getRepository($type)->findByEmail($email);
        } catch (NotFoundException) {
            return null;
        }

        return $entity instanceof AuthenticatableInterface ? $entity : null;
    }

    /**
     * Get entity class name for type
     */
    public function getEntityClass(string $type): string
    {
        if (!isset($this->entities[$type])) {
            throw new \InvalidArgumentException(sprintf('Unknown entity type: %s', $type));
        }

        return $this->entities[$type]['class'];
    }

    /**
     * Get type for entity instance
     */
    public function getTypeForEntity(AuthenticatableInterface $entity): string
    {
        $className = get_class($entity);

        foreach ($this->entities as $type => $data) {
            if ($data['class'] === $className || is_a($className, $data['class'], true)) {
                return $type;
            }
        }

        throw new \InvalidArgumentException(sprintf('Unknown entity class: %s', $className));
    }

    /**
     * Check if entity type is registered
     */
    public function has(string $type): bool
    {
        return isset($this->entities[$type]);
    }

    /**
     * Get all registered entity types
     */
    public function getRegisteredTypes(): array
    {
        return array_keys($this->entities);
    }
}
