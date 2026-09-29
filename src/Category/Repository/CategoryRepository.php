<?php
namespace Skeletor\Category\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Skeletor\Core\TableView\Repository\TableViewRepository;

class CategoryRepository extends TableViewRepository implements CategoryRepositoryInterface
{
    const ENTITY = \Skeletor\Category\Entity\Category::class;
    const FACTORY = \Skeletor\Category\Factory\CategoryFactory::class;

    // @TODO create mapping for tenant category on creation

    public function fetchForApi($tenantId)
    {
        $items = [];
        foreach ($this->mapper->fetchForApi($tenantId) as $data) {
            $items[] = $this->make($data);
        }

        return $items;
    }

    public function fetchChildrenIds($categoryIds): array
    {
        $items = [];
        foreach ($this->mapper->fetchChildren($categoryIds) as $data) {
            $items[] = $data['categoryId'];
        }

        return $items;
    }

    public function getSearchableColumns(): array
    {
        return ['title', 'slug'];
    }

    public function fetchAllArray($params = array()): array
    {
        throw new \Exception('deprecated. use fetch all.');
        $items = [];
        foreach ($this->fetchAll($params, null, null, true) as $data) {
            $items[] = $data;
        }

        return $items;
    }

    public function updateProductCount($catId)
    {
        $category = $this->getById($catId);
        // @TODO set product count
//        $category->setProductCount();
        $this->entityManager->flush();
    }

    public function getChildCategories($categoryId)
    {
        $cats = [];
        $targetCat = $this->getById($categoryId);
        $children = $this->fetchAll(['parent' => $targetCat->getId()]);
        foreach($children as $child) {
            $cats[] = $child;
            $grandChildren = $this->fetchAll(['parent' => $child->getId()]);
            foreach($grandChildren as $grandChild) {
                $cats[] = $grandChild;
            }
        }
        return $cats;
    }
}
