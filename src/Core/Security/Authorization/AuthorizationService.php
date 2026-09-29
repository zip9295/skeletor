<?php
declare(strict_types=1);

namespace Skeletor\Core\Security\Authorization;

use Skeletor\Core\Security\Authentication\AuthenticatableInterface;
use Skeletor\Core\Security\Voter\AbstractResourceVoter;
use Psr\Log\LoggerInterface as Logger;

/**
 * Authorization Service
 * Facade for checking permissions using voters
 */
class AuthorizationService
{
    /** @var AbstractResourceVoter[] */
    private array $voters = [];

    public function __construct(
        private PermissionRegistry $permissionRegistry, private Logger $logger
    ) {}

    /**
     * Register a voter
     */
    public function addVoter(AbstractResourceVoter $voter): void
    {
        $this->voters[] = $voter;
    }

    /**
     * Check if user is granted a permission
     */
    public function isGranted(string $permission, AuthenticatableInterface $user, mixed $subject = null): bool
    {
        // Check permission against role
        if ($this->permissionRegistry->hasPermission($permission, $user->getAuthRole())) {
            // If no voters registered, grant based on permission alone
            if (empty($this->voters)) {
                return true;
            }

            // Check voters for additional logic
            foreach ($this->voters as $voter) {
                if ($voter->vote($user, $permission, $subject)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user can access a specific path
     */
    public function canAccessPath(string $path, AuthenticatableInterface $user): bool
    {
        $permissions = $this->permissionRegistry->getPermissionsForPath($path);

        // If permissions is [null], allow access (logged in users only, no specific permission)
        if ($permissions === [null]) {
            return true;
        }

        if (empty($permissions)) {
            // No permissions defined for this path, deny by default for security
            return false;
        }

        foreach ($permissions as $permission) {
            if ($permission === null) {
                // null means any logged in user can access
                return true;
            }
            $granted = $this->isGranted($permission, $user);
            if ($granted) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all voters
     */
    public function getVoters(): array
    {
        return $this->voters;
    }
}
