<?php
namespace Skeletor\Visitor\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Skeletor\Visitor\Model\VisitorInterface;

interface VisitorFactoryInterface
{
    public static function make($data, EntityManagerInterface $em): VisitorInterface;
}