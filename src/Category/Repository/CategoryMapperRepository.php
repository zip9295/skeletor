<?php
namespace Skeletor\Category\Repository;

use Doctrine\ORM\AbstractQuery;
use Skeletor\Category\Model\CategoryMapper;
use Skeletor\Category\Model\IgnoredCategory;
use Skeletor\Category\Model\MarginRule;
use Skeletor\Category\Repository\CategoryRepository as Category;
use Skeletor\Product\Entity\Product;
use Skeletor\Product\Repository\SupplierRepository;
use Skeletor\Core\TableView\Repository\TableViewRepository;
use Skeletor\Category\Mapper\CategoryMapper as Mapper;
use Skeletor\Category\Model\CategoryMapper as Model;
use Skeletor\Core\Config\Config;

class CategoryMapperRepository extends TableViewRepository
{

    const ENTITY = \Skeletor\Category\Entity\CategoryMapper::class;
    const FACTORY = \Skeletor\Category\Factory\CategoryMapperFactory::class;

    public function getSearchableColumns(): array
    {
        return ['slug1', 'slug2', 'slug3'];
    }

    public function getMappingsByMarginGroupId($marginGroupId)
    {
        return $this->mapper->getMappingsByMarginGroupId($marginGroupId);
    }

    /**
     * @param Model $entity
     * @return MarginRule[]
     */
    public function getMargins(CategoryMapper $entity): array
    {
        $data = [];
        $rules = unserialize($entity->getRules(),['allowed_classes' => false]);
        $limiters = unserialize($entity->getLimiter(),['allowed_classes' => false]);
        $margins = unserialize($entity->getMargin(),['allowed_classes' => false]);
        if($rules && $limiters && $margins) {
            foreach($rules as $key => $rule) {
                $data[] = new MarginRule((int) $limiters[$key],  $margins[$key]);
            }
        }
        return $data;
    }

    public function updateMargins($id, $data)
    {
        return $this->mapper->updateMargins($id, $data);
    }

    public function getMapCounts($catString, $supplierId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('count(a.id) as count')
            ->from(Product::class, 'a')
            ->where('a.supplier = :supplier')
            ->andWhere('a.supplierCategory = :supplierCategory');
        $qb->setParameter(':supplier', $supplierId);
        $qb->setParameter(':supplierCategory', $catString);
        $query = $qb->getQuery();

        return $query->getSingleScalarResult();
    }

    public function getCountForSupplier($supplierId, string $type)
    {
        return $this->getCountPerType($supplierId)[$type];
    }

    public function getCountPerType($supplierId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select("SUM(CASE
	WHEN a.category is null and a.ignored = 0 THEN 1 
    ELSE 0
END) as unmapped,
SUM(CASE
	WHEN a.category is null and a.ignored = 1 THEN 1 
    ELSE 0
END) as ignored,
SUM(CASE
	WHEN a.category is not null THEN 1 
    ELSE 0
END) as mapped")->from(static::ENTITY, 'a')
            ->where('a.supplier = :supplier');
        $qb->setParameter(':supplier', $supplierId);
        $query = $qb->getQuery();

        return $query->getResult(AbstractQuery::HYDRATE_ARRAY)[0];
    }

    public function getMapped($supplierId, $limit, $offset, $order)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')
            ->from(static::ENTITY, 'a')
            ->where('a.supplier = :supplier')
            ->andWhere('a.category IS NOT NULL');
        $qb->setParameter(':supplier', $supplierId);
        $qb->setFirstResult($offset)
            ->setMaxResults($limit);
        if ($order) {
            $qb->addOrderBy('a.' . $order['orderBy'], $order['dir']);
        }
        $mapped = [];
        $query = $qb->getQuery();
        foreach ($query->getResult() as $entity) {
            $mapped[] = static::FACTORY::make(
                $this->entityManager->getUnitOfWork()->getOriginalEntityData($entity), $this->entityManager
            );
        }
        return $mapped;
    }
    public function getUnmapped($supplierId, $limit, $offset)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')
            ->from(static::ENTITY, 'a')
            ->where('a.supplier = :supplier')
            ->andWhere('a.category IS NULL AND a.ignored = 0');
        $qb->setParameter(':supplier', $supplierId);
        $qb->setFirstResult($offset)
            ->setMaxResults($limit);
        $ignored = [];
        $query = $qb->getQuery();
        foreach ($query->getResult() as $entity) {
            $ignored[] = static::FACTORY::make(
                $this->entityManager->getUnitOfWork()->getOriginalEntityData($entity), $this->entityManager
            );
        }
        return $ignored;
    }
    public function getIgnored($supplierId, $limit, $offset)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')
            ->from(static::ENTITY, 'a')
            ->where('a.supplier = :supplier')
            ->andWhere('a.category IS NULL AND a.ignored = 1');
        $qb->setParameter(':supplier', $supplierId);
        $qb->setFirstResult($offset)
            ->setMaxResults($limit);
        $ignored = [];
        $query = $qb->getQuery();
        foreach ($query->getResult() as $entity) {
            $ignored[] = static::FACTORY::make(
                $this->entityManager->getUnitOfWork()->getOriginalEntityData($entity), $this->entityManager
            );
        }
        return $ignored;
    }

    public function fetchResultsWithSearchSource($search, $type, $supplierId, $mappingType, $limit, $offset, $searchType)
    {
        $results = [];
        $entities = $this->mapper->fetchResultsWithSearchSource($search, $type, $supplierId, $mappingType, $limit, $offset, $searchType);
        foreach($entities['results'] as $entity) {
            $results[] = $this->make($entity);
        }
        return ['results' => $results, 'count' => $entities['count']];
    }
}
