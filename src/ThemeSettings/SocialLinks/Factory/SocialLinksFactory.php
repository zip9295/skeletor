<?php

namespace Skeletor\ThemeSettings\SocialLinks\Factory;

use Skeletor\Core\Factory\AbstractFactory;
use Skeletor\ThemeSettings\SocialLinks\Entity\SocialLinks;
use Skeletor\ThemeSettings\SocialLinks\Model\SocialLink;

class SocialLinksFactory extends AbstractFactory
{
    public static function compileEntityForCreate($data, $em)
    {
        $entity = new SocialLinks();
        $entity->platform = $data['platform'];
        $entity->url = $data['url'];
        $entity->position = $data['position'];
        $em->persist($entity);
        $em->flush();
        return $entity;
    }

    public static function make($entity, $entityData = [], $em = null)
    {
        return new SocialLink(
            $entity->id,
            $entity->platform,
            $entity->url);
    }
}