<?php
declare(strict_types = 1);
namespace Skeletor\Core\Acl;

use Skeletor\Core\Config\Config;
use Laminas\Session\ManagerInterface;

class Acl implements AclInterface
{
    const MSG_NOT_LOGGED_IN = 0;
    const MSG_NO_PERMISSIONS = 1;

    private $aclData;

    /** Stored but never read. Kept because apps construct Acl by hand and would break. */
    private $sessionManager;

    private $config;

    private $aclMessages;

    /**
     * Acl constructor.
     *
     * @param ManagerInterface $sessionManager
     * @param Config $config
     * @param $aclData
     */
    public function __construct(ManagerInterface $sessionManager, Config $config, $aclData, $aclMessages)
    {
        $this->aclData = $aclData;
        $this->aclMessages = $aclMessages;
        $this->config = $config;
        $this->sessionManager = $sessionManager;
    }

    public function getMessage($id)
    {
        return $this->aclMessages[$id];
    }

    public function isGuestPath($requestedPath)
    {
        $requestedPath = rtrim($requestedPath, '/');
        foreach ($this->aclData[AclInterface::GUEST_LEVEL] as $path) {
            $path = str_replace('admin', $this->config->adminPath, rtrim($path, '/'));
            $wildcard = strstr($path, '*', true);
            if ($wildcard && strpos($requestedPath, $wildcard) !== false) {
                return true;
            }
            if ($requestedPath === $path) {
                return true;
            }
        }
        return false;
    }


    public function canAccess($entity, $requestedPath): bool
    {
        // Use getAuthRole() if available (for AuthenticatableInterface), fallback to getRole()
        $level = method_exists($entity, 'getAuthRole') ? $entity->getAuthRole() : $entity->getRole();

        // Check if this role level exists in ACL data
        if (!isset($this->aclData[$level])) {
            return false;
        }

        foreach ($this->aclData[$level] as $path) {
            $path = str_replace('admin', $this->config->adminPath, $path);
            $wildcard = strstr($path, '*', true);
            if ($wildcard && strpos($requestedPath, $wildcard) !== false) {
                return true;
            }
            $idWildcard = strstr($path, '/id/', true);
            if ($idWildcard && strpos($requestedPath, (string) $entity->getId()) !== false) {
                return true;
            }
            $tenantWildcard = strstr($path, '/tenantId/', true);
            if ($tenantWildcard && strpos($requestedPath, (string) $entity->getId()) !== false) {
                // return true if logged in tenantId is owner of this entity
//                return true;
            }
            if ($requestedPath === $path) {
                return true;
            }
        }

        return false;
    }
}
