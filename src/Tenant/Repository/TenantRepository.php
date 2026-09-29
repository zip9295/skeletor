<?php
namespace Skeletor\Tenant\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Skeletor\Core\TableView\Repository\TableViewRepository;

class TenantRepository extends TableViewRepository implements TenantRepositoryInterface
{
    const ENTITY = \Skeletor\Tenant\Entity\Tenant::class;
    const FACTORY = \Skeletor\Tenant\Model\TenantFactory::class;

    /**
     * @param EntityManagerInterface $em
     * @param ClassMetadata $class
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em);
    }

//    public function delete($tenantId): bool
//    {
//        if ($tenantId === 1) {
//            throw new \Exception('This item cannot be deleted.');
//        }
//        $this->mapper->beginTransaction();
//        try {
//            $tenant = $this->getById($tenantId);
//            $this->clientMapper->delete($tenant->getId());
//            $this->mapper->delete($tenantId);
//        } catch (\Exception $e) {
//            $this->mapper->rollBackTransaction();
//            throw $e;
//        }
//        $this->mapper->commitTransaction();
//        return true;
//    }

    public function getSearchableColumns(): array
    {
        return ['name'];
    }
}
