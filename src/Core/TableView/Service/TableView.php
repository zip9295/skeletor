<?php
namespace Skeletor\Core\TableView\Service;

use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Filter\FilterInterface as Filter;
use Skeletor\Core\Service\CrudService as Service;
use Skeletor\Core\TableView\Repository\TableViewRepositoryInterface as Repository;
use Skeletor\Tenant\Repository\TenantRepositoryInterface as TenantRepo;
use Skeletor\User\Service\Session;

abstract class TableView extends Service
{
    /**
     * @param Repository $repository
     * @param Session $userSession
     * @param Logger $logger
     * @param Filter|null $filter
     * @param TenantRepo|null $tenant
     */
    public function __construct(
        Repository $repository, Session $userSession, Logger $logger, ?Filter $filter = null,
        protected ?TenantRepo $tenant = null, ?\Skeletor\Core\Activity\Service\Activity $activity = null,
    ) {
        parent::__construct($repository, $userSession, $logger, $tenant, $filter, $activity);
    }


    public function prepareEntities($entities)
    {
        $items = [];
        foreach ($entities as $itemData) {
            if(method_exists($itemData,'getCreatedAt') && $itemData->getCreatedAt()) {
                $createdAt = $itemData->getCreatedAt()->format('d.m.Y');
            }
            if(method_exists($itemData, 'getUpdatedAt') && $itemData->getCreatedAt()) {
                $updatedAt = $itemData->getUpdatedAt()->format('d.m.Y');
            }
            $itemArray = [];
            if(isset($createdAt)) {
                $itemArray['createdAt'] = $createdAt;
            }
            if(isset($updatedAt)) {
                $itemArray['updatedAt'] = $updatedAt;
            }
            if(isset($itemArray['isActive'])) {
                $itemArray['isActive'] = ($itemArray['isActive'] === 1 || $itemArray['isActive'] === true) ? 'Yes' : 'No';
            }
            $items[] = [
                'columns' => $itemArray,
                'id' => $itemData->getId(),
            ];
        }
        return $items;
    }

    public function fetchTableData(
        $search, $filter, $offset, $limit, $order, $uncountableFilter = null, $idsToInclude = [], $idsToExclude = []
    ) {
        $items = $this->repo->fetchTableData($search, $filter, $offset, $limit, $order, $uncountableFilter, $idsToInclude, $idsToExclude);
        return [
            'entities' => $this->prepareEntities($items['items']),
            'countColumnData' => $items['countColumnData']
        ];
    }

    public function getTotalCount(array $uncountableFilter = [])
    {
        return $this->repo->getTotalCount($uncountableFilter);
    }
}