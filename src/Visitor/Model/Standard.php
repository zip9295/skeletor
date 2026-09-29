<?php
namespace Skeletor\Visitor\Model;

use Skeletor\Image\Model\Image;

class Standard extends Visitor
{

    public function __construct(
        $id, $password, $email, $isActive, $ipv4, $lastLogin, $firstName, $lastName, $createdAt, $updatedAt,
//        ?Image $avatar, $displayName
    ) {
        $role = self::ROLE_STANDARD;
        parent::__construct(
            $id, $password, $email, $role, $isActive, $firstName, $lastName, $ipv4, $lastLogin, $createdAt, $updatedAt,
//            $avatar, $displayName
        );
    }
}