<?php

namespace Skeletor\User\Model;

/**
 * Class Admin.
 * Represents admin user dto.
 *
 * @package Skeletor\User\Model
 */
class Admin extends User
{
    private $redirectTo = '/user/view/';

    public function __construct(
        $id, $password, $email, $isActive, $displayName, $firstName, $lastName, $ipv4 = null, $lastLogin = null, $createdAt = null, $updatedAt = null
    ) {
        $role = self::ROLE_ADMIN;
        parent::__construct(
            $id, $password, $email, $role, $isActive, $displayName, $firstName, $lastName, $ipv4, $lastLogin, $createdAt, $updatedAt
        );
    }
}