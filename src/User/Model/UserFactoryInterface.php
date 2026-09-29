<?php
namespace Skeletor\User\Model;

use Doctrine\ORM\EntityManagerInterface;

interface UserFactoryInterface
{
    public static function make($itemData, EntityManagerInterface $em): UserInterface;
}