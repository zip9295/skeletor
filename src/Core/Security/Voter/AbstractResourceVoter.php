<?php
declare(strict_types=1);

namespace Skeletor\Core\Security\Voter;

use Skeletor\Core\Security\Authentication\AuthenticatableInterface;

/**
 * Base voter with common logic for resource-based permissions
 * Inspired by Symfony Security Voters
 */
abstract class AbstractResourceVoter
{
    /**
     * Determine if the attribute and subject are supported by this voter
     */
    abstract protected function supports(string $attribute, mixed $subject): bool;

    /**
     * Perform voting logic
     */
    abstract protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        AuthenticatableInterface $user
    ): bool;

    /**
     * Main vote method
     */
    public function vote(AuthenticatableInterface $user, string $attribute, mixed $subject = null): bool
    {
        if (!$this->supports($attribute, $subject)) {
            return false;
        }

        return $this->voteOnAttribute($attribute, $subject, $user);
    }

    /**
     * Check if user has role
     */
    protected function hasRole(AuthenticatableInterface $user, int $role): bool
    {
        return $user->getAuthRole() === $role;
    }

    /**
     * Check if user has any of the roles
     */
    protected function hasAnyRole(AuthenticatableInterface $user, array $roles): bool
    {
        return in_array($user->getAuthRole(), $roles);
    }

    /**
     * Check if user is owner of the subject
     */
    protected function isOwner(AuthenticatableInterface $user, mixed $subject): bool
    {
        if (!is_object($subject)) {
            return false;
        }

        if (method_exists($subject, 'getId')) {
            return $subject->getId() === $user->getId();
        }

        return false;
    }
}
