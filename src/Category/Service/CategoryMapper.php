<?php
namespace Skeletor\Category\Service;

use Skeletor\Category\Repository\CategoryMapperRepository;
use Skeletor\Category\Repository\TenantCategoryMapperRepository;
use Skeletor\Product\Repository\ProductRepository;
use Skeletor\Product\Service\Product;
use Skeletor\Product\Service\Supplier;
use Skeletor\Tenant\Service\Tenant;
use GuzzleHttp\Exception\GuzzleException;
use Skeletor\Core\TableView\Service\TableView;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Tenant\Repository\TenantRepository;
use Skeletor\User\Service\Session;
use Skeletor\Product\Filter\Supplier as SupplierFilter;
use Skeletor\Category\Filter\Category as CategoryFilter;

class CategoryMapper extends TableView
{
    /**
     * @param CategoryMapperRepository $repo
     * @param User $user
     * @param Logger $logger
     * @param SupplierFilter $filter
     * @param Activity $activity
     */
    public function __construct(
        CategoryMapperRepository $repo, Session $user, Logger $logger, private Supplier $supplier,
        Tenant $tenant,
        \Skeletor\Core\Activity\Service\Activity $activity,
//        private ProductRepository $productRepo, private Product $productService,
//        private TenantCategoryMapperRepository $tenantCategoryMapperRepo
    ) {
        parent::__construct($repo, $user, $logger, activity: $activity);
    }

    public function getEntityData($id)
    {
        $category = $this->repo->getById($id);

        return [
            'id' => $category->getId(),
            'source1' => $category->getSource1(),
            'source2' => $category->getSource2(),
            'source3' => $category->getSource3(),
            'categoryId' => $category->getCategory()->getTitle(),
            'supplierId' => $category->getSupplier()->getName(),
            'status' => $category->getStatus(),
            'createdAt' => $category->getUpdatedAt()->format('m.d.Y'),
            'updatedAt' => $category->getCreatedAt()->format('m.d.Y'),
        ];
    }

    public function getCountPerType($supplierId)
    {
        return $this->repo->getCountPerType($supplierId);
    }

    public function prepareEntities($entities)
    {
        $items = [];
        foreach ($entities as $category) {
            $itemData = [
                'id' => $category->getId(),
                'source1' => $category->getSource1(),
                'source2' => $category->getSource2(),
                'source3' => $category->getSource3(),
                'categoryId' => $category->getCategory()->getTitle(),
                'supplierId' => $category->getSupplier()->getName(),
                'status' => $category->getStatus(),
                'createdAt' => $category->getUpdatedAt()->format('m.d.Y'),
                'updatedAt' => $category->getCreatedAt()->format('m.d.Y'),
            ];
            $items[] = [
                'columns' => $itemData,
                'id' => $category->getId(),
            ];
        }
        return $items;
    }

    public function compileTableColumns()
    {
        $suppliers = [];
        foreach ($this->supplier->getEntities() as $supplier) {
            $suppliers[] = $supplier->getName();
        }
        return [
            ['name' => 'source1', 'label' => 'Source1'],
            ['name' => 'source2', 'label' => 'Source2'],
            ['name' => 'source3', 'label' => 'Source3'],
            ['name' => 'status', 'label' => 'Status'],
            ['name' => 'category', 'label' => 'Category'],
            ['name' => 'supplier', 'label' => 'Supplier', 'filterData' => $suppliers],
            ['name' => 'updatedAt', 'label' => 'Updated at'],
            ['name' => 'createdAt', 'label' => 'Created at'],
        ];
    }

    /**
     * @throws \Exception
     * @throws GuzzleException
     */
    public function updateFromArray(array $data)
    {
//        $oldData = $this->getEntities(['supplierId' => $data['supplierId'],
//            'source1' => $data['source1'],
//            'source2' => $data['source2'],
//            'source3' => $data['source3'],
//        ]);
        $result =  $this->repo->update($data);
//        if(isset($oldData[0]) && $oldData[0]->getCategory()?->getId() != $data['categoryId']) {
//            $this->changeProductCategories($oldData[0]->getCategory()?->getId(), $data['categoryId'], $data['supplierId'],
//            $oldData[0]->getId());
//        }
        return $result;
    }

    public function getMappingsByMarginGroupId($marginGroupId)
    {
        return $this->repo->getMappingsByMarginGroupId($marginGroupId);
    }
    
    public function fetchAllArray($filter = [])
    {
        return $this->repo->fetchAllArray($filter);
    }

    public function getMargins($id)
    {
        $entity = $this->getById($id);
        return $this->repo->getMargins($entity);
    }

    public function getMarginGroupId($id)
    {
        return $this->getById($id)->getMarginGroupId();
    }

    public function updateMargins($id, $data)
    {
        return $this->repo->updateMargins($id, $data);
    }

    public function getMapCounts($catString, $supplierId)
    {
        return $this->repo->getMapCounts($catString, $supplierId);
    }

    public function getCountForSupplier($supplierId, string $type = '')
    {
        $this->parseMappingType($type);

        return $this->repo->getCountForSupplier($supplierId, $type);
    }

    public function parseMappingType(string $type)
    {
        $allowedTypes = ['mapped', 'ignored', 'unmapped'];
        if (!in_array($type, $allowedTypes)){
            throw new \InvalidArgumentException(sprintf('Category mapping type: %s is not supported', $type));
        }
    }

    public function getMapped($supplierId, $limit, $offset, $order = null)
    {
        return $this->repo->getMapped($supplierId, $limit, $offset, $order);
    }

    public function getUnmapped($supplierId, $limit, $offset)
    {
        return $this->repo->getUnmapped($supplierId, $limit, $offset);
    }

    public function getIgnored($supplierId, $limit, $offset)
    {
        return $this->repo->getIgnored($supplierId, $limit, $offset);
    }

    /**
     * @throws \Exception
     * @throws GuzzleException
     */
    private function changeProductCategories($oldCatId, $newCatId, $supplierId, $mappingId)
    {
        if ($oldCatId === null) {
            return;
        }
        $itemsToUpdate = $this->productRepo->fetchAll(['category' => $oldCatId, 'supplierId' => $supplierId, 'mappingId' => $mappingId]);
        $idsToUpdate = [];
        foreach ($itemsToUpdate as $item) {
            $this->productRepo->updateField('category', $newCatId, $item->getId());
            $idsToUpdate[] = $item->getId();
        }
         $this->productService->syncProducts($idsToUpdate);
    }

    public function fetchResultsWithSearchSource($search, $type, $supplierId, $mappingType, $limit, $offset, $searchType)
    {
        return $this->repo->fetchResultsWithSearchSource($search, $type, $supplierId, $mappingType, $limit, $offset, $searchType);
    }

    public function fetchResultsWithSearchExternal($search, $type, $tenantId, $mappingType, $limit, $offset, $searchType)
    {
       return $this->tenantCategoryMapperRepo->fetchResultsWithSearchExternal($search, $type, $tenantId, $mappingType, $limit, $offset, $searchType);
    }
}