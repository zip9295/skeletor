<?php
declare(strict_types = 1);
namespace Skeletor\Core\Acl;

use Skeletor\User\Model\UserInterface;

interface AclInterface
{
    const GUEST_LEVEL = 0;


    public function canAccess(UserInterface $user, string $requestedPath): bool;
}