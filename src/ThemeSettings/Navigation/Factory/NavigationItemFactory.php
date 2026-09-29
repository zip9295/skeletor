<?php

namespace Skeletor\ThemeSettings\Navigation\Factory;

use Skeletor\Core\Factory\AbstractFactory;
use Skeletor\ThemeSettings\Navigation\Entity\Navigation;
use Skeletor\ThemeSettings\Navigation\Entity\NavigationItem;

class NavigationItemFactory extends AbstractFactory
{
    public static function make($entity, $entityData = [], $em = null)
    {
        $parent = null;
        if($entity->parent) {
            $parent = self::make($entity->parent);
        }
        $data = [
            'id' => $entity->id,
            'label' => $entity->label,
            'url' => $entity->url,
            'icon' => $entity->icon,
            'position' => $entity->position,
            'openInNewTab' => $entity->openInNewTab,
            'parent' => $parent
        ];
        return new \Skeletor\ThemeSettings\Navigation\Model\NavigationItem(...$data);
    }

    public static function compileEntityForCreate($data, $em)
    {
        $navigation = $em->getRepository(Navigation::class)->find($data['navigationId']);
        if(isset($data['parentId']) && $data['parentId']) {
            $data['parent'] = $em->getRepository(NavigationItem::class)->find($data['parentId']);
        } else {
            $data['parent'] = null;
        }
        $entity = new NavigationItem();
        $entity->label = $data['label'];
        $entity->url = $data['url'];
        $entity->icon = $data['icon'];
        $entity->position = $data['position'];
        $entity->parent = $data['parent'];
        $entity->openInNewTab = (isset($data['openInNewTab']) && $data['openInNewTab'] != 0) ? 1 : 0;
        $entity->navigation = $navigation;
        $em->persist($entity);
        $em->flush();
        return $entity->id;
    }
}