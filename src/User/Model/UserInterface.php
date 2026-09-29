<?php
namespace Skeletor\User\Model;

interface UserInterface
{
    const ROLE_GUEST = 0;
    const ROLE_ADMIN = 1;
    const ROLE_EDITOR = 2;
    const ROLE_JOURNALIST = 3;
    const ROLE_AUTHOR = 4;
    const ROLE_STAFF = 5;

    public function getPassword();
    public function getEmail();
    public function getRedirectPath();
    public function getRole();
    public function getIsActive();
    public function getDisplayName();
}