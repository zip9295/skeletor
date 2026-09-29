<?php
namespace Skeletor\Visitor\Model;

interface VisitorInterface
{
    const ROLE_GUEST = 0;
    const ROLE_STANDARD = 1;
    const ROLE_LEVEL1 = 2;
    const ROLE_LEVEL2 = 3;
    const ROLE_LEVE3 = 4;

    public function getPassword();
    public function getEmail();
    public function getRole();
    public function getIsActive();
}