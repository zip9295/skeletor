<?php
namespace Skeletor\Category\Repository;

use Skeletor\Category\Repository\CategoryRepository as Category;
use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Category\Mapper\TenantCategoryMapper as Mapper;
use Skeletor\Category\Model\TenantCategoryMapper as Model;
use Skeletor\Core\Config\Config;
use Skeletor\Core\TableView\Repository\TableViewRepository;

class TenantCategoryMapperRepository extends TableViewRepository
{
    const ENTITY = \Skeletor\Category\Entity\TenantCategoryMapper::class;
    const FACTORY = \Skeletor\Category\Factory\TenantCategoryMapperFactory::class;


    public function getCountForTenant(int $tenantId, int $categoryId)
    {
        return $this->mapper->getCountForTenant($tenantId, $categoryId);
    }

    public function getCountPerType($tenantId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(a.id) as count')
            ->from(static::ENTITY, 'a')
            ->where('a.remoteCategory = 0')
            ->andWhere('a.tenant = :tenantId');
        $qb->setParameter(':tenantId', $tenantId);
        $result['unmapped'] = $qb->getQuery()->getSingleScalarResult();
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(a.id) as count')
            ->from(static::ENTITY, 'a')
            ->where('a.remoteCategory != 0')
            ->andWhere('a.tenant = :tenantId');
        $qb->setParameter(':tenantId', $tenantId);
        $result['mapped'] = $qb->getQuery()->getSingleScalarResult();

        return $result;
    }

    public function getMapped($tenantId, $limit, $offset)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')
            ->from(static::ENTITY, 'a')
            ->where('a.localCategory != 0')
            ->andWhere('a.tenant = :tenantId')
            ->setMaxResults($limit)
            ->setFirstResult($offset);
        $qb->setParameter(':tenantId', $tenantId);
        $mapped = [];
        foreach($qb->getQuery()->getResult() as $entity) {
            var_dump($entity);
            die();
            $mapped[] = $this->make($entity);
        }
        return $mapped;
    }

    public function fetchResultsWithSearchExternal($search, $type, $tenantId, $mappingType, $limit, $offset, $searchType)
    {
        $results = [];
        $entities = $this->mapper->fetchResultsWithSearchExternal($search, $type, $tenantId, $mappingType, $limit, $offset, $searchType);
        foreach($entities['results'] as $entity) {
            $results[] = $this->make($entity);
        }
        return ['results' => $results, 'count' => $entities['count']];
    }

    public function getSearchableColumns(): array
    {
        return ['id'];
    }
}
