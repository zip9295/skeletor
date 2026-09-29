<?php
namespace Skeletor\Tenant\Service;

use Skeletor\Tenant\Repository\TenantRepository;
use Skeletor\Core\TableView\Service\TableView as TableView;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Tenant\Repository\TenantRepositoryInterface;
use Skeletor\User\Service\Session;

class Tenant extends TableView
{

    /**
     * @param TenantRepository $repository
     * @param Session $userSession
     * @param Logger $logger
     * @param \Skeletor\Tenant\Filter\Tenant $filter
     */
    public function __construct(
        TenantRepositoryInterface $repository, Session $userSession, Logger $logger,
        \Skeletor\Tenant\Filter\Tenant $filter,
        \Skeletor\Core\Activity\Service\Activity $activity) {
        parent::__construct($repository, $userSession, $logger, $filter, activity: $activity);
    }

    public function prepareEntities($entities)
    {
        $items = [];
        foreach ($entities as $tenant) {
            $item = [
                'id' => $tenant->getId(),
                'name' =>  [
                    'value' => $tenant->getName(),
                    'editColumn' => true,
                ],
                'email' => $tenant->getEmail(),
                'createdAt' => $tenant->getCreatedAt()->format('d.m.Y'),
                'updatedAt' => $tenant->getUpdatedAt()->format('d.m.Y'),
            ];
            $items[] = [
                'columns' => $item,
                'id' => $tenant->getId(),
            ];
        }

        return $items;
    }

    public function compileTableColumns()
    {
        $columnDefinitions = [
            ['name' => 'name', 'label' => 'Name'],
            ['name' => 'email', 'label' => 'email'],
            ['name' => 'updatedAt', 'label' => 'Updated at'],
            ['name' => 'createdAt', 'label' => 'Created at'],
        ];

        return $columnDefinitions;
    }
}