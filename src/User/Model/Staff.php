<?php
namespace Skeletor\User\Model;

/**
 * Class Staff.
 * Represents admin user dto.
 *
 * @package Its\User\Model
 */
class Staff extends User
{
    protected $redirectPath = '/user/view/';

    public function __construct(
        $id, $password, $email, $isActive, $displayName, $firstName, $lastName, $ipv4 = null, $lastLogin = null, $createdAt = null, $updatedAt = null
    ) {
        $role = self::ROLE_STAFF;
        parent::__construct(
            $id, $password, $email, $role, $isActive, $displayName, $firstName, $lastName, $ipv4, $lastLogin, $createdAt, $updatedAt
        );
    }
}