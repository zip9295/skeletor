<?php
namespace Skeletor\User\Repository;

use Skeletor\Core\Repository\RepositoryInterface;
use Skeletor\User\Model\UserInterface as User;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(string $email);

    public function emailExists($email): bool;
}