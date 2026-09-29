<?php
namespace Skeletor\Category\Repository;

use Skeletor\Model\Model;
use Skeletor\Repository\RepositoryInterface;

class CategoryTenantSettings implements RepositoryInterface
{

    public function __construct(
        private \Skeletor\Category\Mapper\CategoryTenantSettings $mapper, private CategoryRepository $categoryRepository
    ) {
    }

    /**
     * @throws \Exception
     * @return Model[]
     */
    public function fetchAll($params = [], $limit = null, $order = null): array
    {
        $items = [];
        foreach ($this->mapper->fetchAll($params, $limit, $order) as $data) {
            $items[] = $this->make($data);
        }

        return $items;
    }

    public function delete(int $id): bool
    {
        return $this->mapper->delete($id);
    }

    /**
     * @throws \Exception
     */
    public function update($data, $addressData = null): int
    {
        return $this->mapper->update($data);
    }

    /**
     * @throws \Exception
     */
    public function create($data, $addressData = null): int
    {
        return $this->mapper->insert($data);
    }

    /**
     * @throws \Skeletor\Mapper\NotFoundException
     */
    public function getById(int $id): Model
    {
        return $this->make($this->mapper->fetchById($id));
    }

    /**
     * @throws \Skeletor\Mapper\NotFoundException
     */
    public function make($modelData): Model
    {
        $modelData['category'] = $this->categoryRepository->getById($modelData['categoryId']);
        unset($modelData['categoryId']);
        return new \Skeletor\Product\Model\CategoryTenantSettings(...$modelData);
    }
}