<?php

namespace Skeletor\ThemeSettings\Navigation\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Skeletor\Core\Factory\AbstractFactory;
use Skeletor\ThemeSettings\Navigation\Entity\Navigation;

class NavigationFactory extends AbstractFactory
{
    public static function compileEntityForCreate($data, $em)
    {
        $navigation = new Navigation();
        $navigation->label = $data['label'];
        $em->persist($navigation);
        $em->flush();
        return $navigation->id;
    }

    public static function make($entity, $entityData = [], $em = null)
    {
        $navigationData = $em->getUnitOfWork()->getOriginalEntityData($entity);
        $data = [
            'id' => $navigationData['id'],
            'label' => $navigationData['label'],
            'createdAt' => $navigationData['createdAt'],
            'updatedAt' => $navigationData['updatedAt']
        ];
        $items = new ArrayCollection();
        if(isset($navigationData['items'])) {
            foreach ($navigationData['items'] as $item) {
                $items[] = NavigationItemFactory::make($item, [], $em);
            }
        }
        $data['items'] = $items;
        return new \Skeletor\ThemeSettings\Navigation\Model\Navigation(...$data);
    }
}