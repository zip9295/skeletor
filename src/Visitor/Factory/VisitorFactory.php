<?php
namespace Skeletor\Visitor\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Skeletor\Visitor\Model\Standard;
use Skeletor\Visitor\Model\Visitor;
use Skeletor\Visitor\Model\VisitorInterface;

class VisitorFactory implements VisitorFactoryInterface
{
    public static function compileEntityForUpdate($data, EntityManagerInterface $em)
    {
        $visitor = $em->getRepository(\Skeletor\Visitor\Entity\Visitor::class)->find($data['id']);
        unset($data['subscriptions']);
        unset($data['displayName']);
        unset($data['avatar']);
        $visitor->populateFromDto(new \Skeletor\Visitor\Model\Visitor(...$data));

        return $visitor->getId();
    }

    public static function compileEntityForCreate($data, EntityManagerInterface $em)
    {
        $visitor = new \Skeletor\Visitor\Entity\Visitor();
        $data['id'] = null;
        unset($data['subscriptions']);
        unset($data['displayName']);
        unset($data['avatar']);
        $visitor->populateFromDto(new \Skeletor\Visitor\Model\Visitor(...$data));
        $em->persist($visitor);

        return $visitor->getId();
    }

    public static function make($data, EntityManagerInterface $em): VisitorInterface
    {
        switch ($data['role'])
        {
            case Visitor::ROLE_STANDARD:
                $visitor = Standard::class;
                break;

            default:
                throw new \InvalidArgumentException('Invalid role provided: ' . $data['role']);
                break;
        }
        unset($data['role']);

        return new $visitor(...$data);
    }
}