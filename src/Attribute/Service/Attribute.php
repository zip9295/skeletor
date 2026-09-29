<?php

namespace Skeletor\Attribute\Service;

use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\TableView\Service\Table;
use \Skeletor\Attribute\Repository\Attribute as Repository;
use Skeletor\User\Service\Session;
use Skeletor\Tenant\Repository\TenantRepository;
use Skeletor\Attribute\Filter\Attribute as Filter;

class Attribute extends Table
{
    public function __construct(Repository $repository, Session $session, Logger $logger,
        TenantRepository $tenantRepository, Filter $filter)
    {
        parent::__construct($repository, $session, $logger, $tenantRepository, $filter);
    }


    function compileTableColumns()
    {
        return [
            ['name' => 'id', 'label' => '#'],
            ['name' => 'name', 'label' => 'Attribute Name'],
            ['name' => 'createdAt', 'label' => 'Created At'],
            ['name' => 'updatedAt', 'label' => 'Updated At'],
        ];
    }
}