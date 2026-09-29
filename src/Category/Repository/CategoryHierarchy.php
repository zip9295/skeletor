<?php
namespace Skeletor\Category\Repository;

use Skeletor\Model\Model;
use Skeletor\Repository\RepositoryInterface;

class CategoryHierarchy implements RepositoryInterface
{
    public function __construct(
        private \Skeletor\Category\Mapper\CategoryHierarchy $mapper, private CategoryRepository $categoryRepository)
    {
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
        $modelData['data'] = $this->insertCatModels(unserialize($modelData['data']));
        return new \Skeletor\Product\Model\CategoryHierarchy(...$modelData);
    }

    /**
     * @throws \Skeletor\Mapper\NotFoundException
     */
    public function insertCatModels($modelData): array
    {
        $newData = [];
        foreach ($modelData as $index => $value) {
            foreach ($value as $key => $value) {
                if ($key === 'topLevelCategory') {
                    $newData[$index][$key] = $this->categoryRepository->getById($value['id']);
                }
                if ($key === 'secondLevelCategories') {
                    foreach ($value as $keySecLevel => $secLevelValues) {
                        $newData[$index][$key][$keySecLevel][] = $this->categoryRepository->getById($secLevelValues['id']);
                        if (isset($secLevelValues['thirdLevelCategories'])) {
                            foreach ($secLevelValues['thirdLevelCategories'] as $thirdLevelCategory) {
                                $newData[$index][$key][$keySecLevel]['thirdLevelCategories'][] = $this->categoryRepository->getById($thirdLevelCategory['id']);
                            }
                        }
                    }
                }
            }
        }
        return $newData;
    }
}