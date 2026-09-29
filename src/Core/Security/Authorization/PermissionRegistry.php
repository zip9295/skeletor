<?php
declare(strict_types=1);

namespace Skeletor\Core\Security\Authorization;

/**
 * Permission Registry
 * Maps permissions to roles and routes to permissions
 */
class PermissionRegistry
{
    /** @var array<string, int[]> Permission => [role IDs] */
    private array $permissions = [];

    /** @var array<string, string|string[]> Path => permission(s) */
    private array $routePermissions = [];

    /** @var array<int, int[]> Role => [parent role IDs] */
    private array $roleHierarchy = [];

    public function __construct(array $config = [])
    {
        if (isset($config['permissions'])) {
            $this->permissions = $config['permissions'];
        }

        if (isset($config['routes'])) {
            $this->routePermissions = $config['routes'];
        }

        if (isset($config['roles'])) {
            $this->roleHierarchy = $config['roles'];
        }
    }

    /**
     * Check if role has permission
     */
    public function hasPermission(string $permission, int $role): bool
    {
        if (!isset($this->permissions[$permission])) {
            return false;
        }

        $allowedRoles = $this->permissions[$permission];

        // Check direct role
        if (in_array($role, $allowedRoles)) {
            return true;
        }

        // Check inherited roles
        foreach ($this->getInheritedRoles($role) as $inheritedRole) {
            if (in_array($inheritedRole, $allowedRoles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get permissions required for a path
     */
    public function getPermissionsForPath(string $path): array
    {
        $path = rtrim($path, '/');

        foreach ($this->routePermissions as $pattern => $permissions) {
            $pattern = rtrim($pattern, '/');

            // Exact match
            if ($path === $pattern) {
                return is_array($permissions) ? $permissions : [$permissions];
            }

            // Wildcard match
            if (str_contains($pattern, '*')) {
                $wildcardPattern = str_replace('\*', '.*', preg_quote($pattern, '/'));
                if (preg_match('/^' . $wildcardPattern . '$/', $path)) {
                    return is_array($permissions) ? $permissions : [$permissions];
                }
            }
        }

        return [];
    }

    /**
     * Get inherited roles for a role
     */
    private function getInheritedRoles(int $role): array
    {
        if (!isset($this->roleHierarchy[$role])) {
            return [];
        }

        $inherited = [];
        foreach ($this->roleHierarchy[$role] as $parentRole) {
            $inherited[] = $parentRole;
            $inherited = array_merge($inherited, $this->getInheritedRoles($parentRole));
        }

        return array_unique($inherited);
    }

    /**
     * Add a permission
     */
    public function addPermission(string $permission, array $roles): void
    {
        $this->permissions[$permission] = $roles;
    }

    /**
     * Map route to permission
     */
    public function addRoute(string $path, string|array $permissions): void
    {
        $this->routePermissions[$path] = $permissions;
    }

    /**
     * Add role hierarchy
     */
    public function addRoleHierarchy(int $role, array $inherits): void
    {
        $this->roleHierarchy[$role] = $inherits;
    }
}
