<?php

namespace Skeletor\Category\Factory;

use Skeletor\Product\Entity\Supplier;
use Skeletor\Product\Factory\SupplierFactory;
use Skeletor\Tenant\Entity\Tenant;
use Skeletor\Tenant\Model\TenantFactory;
use Skeletor\Image\Entity\Image;
use function Skeletor\Product\Factory\SupplierFieldsMappingFactory;

class CategoryMapperFactory
{
    public static function compileEntityForUpdate($data, $em)
    {
        $mapper = $em->getRepository(\Skeletor\Category\Entity\CategoryMapper::class)->find($data['id']);
        if ($data['supplierId']) {
            $supplier = $em->getRepository(Supplier::class)->find($data['supplierId']);
            $mapper->setSupplier($supplier);
        }
        if ($data['categoryId'] && $data['categoryId'] != '-1' && $data['categoryId'] != 0) {
            $category = $em->getRepository(\Skeletor\Category\Entity\Category::class)->find($data['categoryId']);
            $mapper->setCategory($category);
        }
        unset($data['supplierId']);
        unset($data['categoryId']);
        $categoryDto = new \Skeletor\Category\Model\CategoryMapper(...$data);
        $mapper->populateFromDto($categoryDto);
        $em->persist($mapper);

        return $mapper->getId();
    }

    public static function compileEntityForCreate($data, $em)
    {
        $mapper = new \Skeletor\Category\Entity\CategoryMapper();
        $data['id'] = null;
        if ($data['supplierId']) {
            $supplier = $em->getRepository(Supplier::class)->find($data['supplierId']);
            $mapper->setSupplier($supplier);
        }
        if ($data['categoryId'] && $data['categoryId'] != '-1' && $data['categoryId'] != 0) {
            $category = $em->getRepository(\Skeletor\Category\Entity\Category::class)->find($data['categoryId']);
            $mapper->setCategory($category);
        }
        unset($data['supplierId']);
        unset($data['categoryId']);
        $mapper->populateFromDto(new \Skeletor\Category\Model\CategoryMapper(...$data));
        $em->persist($mapper);

        return $mapper->getId();
    }

    public static function make($itemData, $em): \Skeletor\Category\Model\CategoryMapper
    {
        if (isset($itemData['supplierId']) && $itemData['supplierId']) {
            $itemData['supplier'] = SupplierFactory::make($em->getUnitOfWork()->getOriginalEntityData($itemData['supplier']), $em);
        } else {
            $itemData['supplier'] = null;
        }
        if (isset($itemData['categoryId']) && $itemData['categoryId']) {
            $itemData['category'] = CategoryFactory::make($em->getUnitOfWork()->getOriginalEntityData($itemData['category']), $em);
        } else {
            $itemData['category'] = null;
        }
        unset($itemData['supplierId']);
        unset($itemData['categoryId']);

        return new \Skeletor\Category\Model\CategoryMapper(...$itemData);
    }
}