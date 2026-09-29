<?php
namespace Skeletor\Visitor\Repository;

use Skeletor\Core\Repository\RepositoryInterface;
use Skeletor\Visitor\Model\VisitorInterface as Visitor;

interface VisitorRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(string $email): Visitor;

    public function emailExists($email): bool;
}