<?php

namespace Skeletor\Category\Factory;

use Skeletor\Tenant\Entity\Tenant;
use Skeletor\Tenant\Model\TenantFactory;
use Skeletor\Image\Entity\Image;

class CategoryFactory
{
    public static function compileEntityForUpdate($data, $em)
    {
        $category = $em->getRepository(\Skeletor\Category\Entity\Category::class)->find($data['id']);
        if ($data['imageId']) {
            $image = $em->getRepository(Image::class)->find($data['imageId']);
            $category->setImage($image);
        }
        if ($data['tenantId']) {
            $tenant = $em->getRepository(Tenant::class)->find($data['tenantId']);
            $category->setTenant($tenant);
        }

        if ($data['parent']) {
            $parent = $em->getRepository(\Skeletor\Category\Entity\Category::class)->find($data['parent']);
            $category->setParent($parent);
        }
        unset($data['tenantId']);
        unset($data['imageId']);
        $data['image'] = null;
        unset($data['parent']);
        $categoryDto = new \Skeletor\Category\Model\Category(...$data);
        $category->populateFromDto($categoryDto);
        $em->persist($category);

        return $category->getId();
    }

    public static function compileEntityForCreate($data, $em)
    {
        $category = new \Skeletor\Category\Entity\Category();
        $data['id'] = null;
        if ($data['imageId']) {
            $image = $em->getRepository(Image::class)->find($data['imageId']);
            $category->setImage($image);
        }
        if ($data['tenantId']) {
            $tenant = $em->getRepository(Tenant::class)->find($data['tenantId']);
            $category->setTenant($tenant);
        }
        if ($data['parent']) {
            $parent = $em->getRepository(\Skeletor\Category\Entity\Category::class)->find($data['parent']);
            $category->setParent($parent);
        }
        unset($data['tenantId']);
        unset($data['imageId']);
        unset($data['image']);
        unset($data['parent']);
        $categoryDto = new \Skeletor\Category\Model\Category(...$data);
        $category->populateFromDto($categoryDto);
        $em->persist($category);

        return $category->getId();
    }

    public static function make($itemData, $em): \Skeletor\Category\Model\Category
    {
//        var_dump($itemData);
//        die();

        if (isset($itemData['imageId']) && $itemData['imageId']) {
            $itemData['image'] = new \Skeletor\Image\Model\Image(...$em->getUnitOfWork()->getOriginalEntityData($itemData['image']));
        } else {
            $itemData['image'] = null;
        }
        if (isset($itemData['tenant']) && $itemData['tenant']) {
            $itemData['tenant'] = TenantFactory::make($em->getUnitOfWork()->getOriginalEntityData($itemData['tenant']), $em);
        } else {
            $itemData['tenant'] = null;
        }
        if ($itemData['parent']) {
            $parent = $em->getUnitOfWork()->getOriginalEntityData($itemData['parent']);
            $itemData['parent'] = static::make($parent, $em);
        } else {
            $itemData['parent'] = null;
        }
        $description = [];
        if ($itemData['description']) {
            if (!json_decode($itemData['description'])) {
//                var_dump($itemData['description']);
//                die();
            }
            foreach (json_decode($itemData['description']) as $block) {
                $description[] = (array) $block;
            }
        }
        $itemData['description'] = $description;
        unset($itemData['tenantId']);
        unset($itemData['imageId']);

        return new \Skeletor\Category\Model\Category(...$itemData);
    }
}