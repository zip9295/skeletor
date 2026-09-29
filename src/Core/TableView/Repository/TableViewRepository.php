<?php
namespace Skeletor\Core\TableView\Repository;

use Skeletor\Core\Repository\CrudRepository;

abstract class TableViewRepository extends CrudRepository implements TableViewRepositoryInterface
{
    abstract function getSearchableColumns(): array;

    public function getJoinableEntities()
    {
        return [];
    }

    public function fetchTableData($search, $filter, $offset, $limit, $order, $uncountableFilter = null, $idsToInclude = [], $idsToExclude = [])
    {
        $items = [];
        $hasJoins = !empty($this->getJoinableEntities());
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')
            ->from(static::ENTITY, 'a');
        if ($hasJoins) {
            $qb->distinct();
        }
        foreach ($this->getJoinableEntities() as $relation => $alias) {
            $qb->leftJoin('a.' . $relation, $alias);
        }

        if (isset($filter['rangeFilters'])) {
            foreach (get_object_vars(json_decode($filter['rangeFilters'])) as $field => $filterData) {
                $value = json_decode($filterData);
                $qb->andWhere(sprintf('a.%s BETWEEN :from AND :to', $field));
                if($value->from === '') {
                    $value->from = new \DateTime('0000-00-00');
                } else {
                    $value->from = new \DateTime($value->from);
                    $value->from->setTime(0, 0, 0);
                }
                if($value->to === '') {
                    $value->to = new \DateTime();
                } else {
                    $value->to = new \DateTime($value->to);
                    $value->to->setTime(23, 59, 59);
                }
                $qb->setParameter(':to', $value->to);
                $qb->setParameter(':from', $value->from);
            }
            unset($filter['rangeFilters']);
        }

        foreach ($filter as $key => $value) {
            $field = str_contains($key, '.') ? $key : 'a.' . $key;
            $param = str_replace('.', '_', $key);
            if (is_string($value)) {
                $value = trim($value, '"');
            }
            if ($value == 'null') {
                $qb->andWhere($qb->expr()->isNull($field));
            } elseif ($value == 'not_null') {
                $qb->andWhere($qb->expr()->isNotNull($field));
            } else {
                if(is_array($value)) {
                    $qb->andWhere($qb->expr()->in($field, implode(',',$value)));
                    continue;
                }
                $qb->andWhere(sprintf('%s = :%s', $field, $param));
                $qb->setParameter($param, $value);
            }
        }
        if ($uncountableFilter) {
            foreach ($uncountableFilter as $key => $value) {
                $field = str_contains($key, '.') ? $key : 'a.' . $key;
                $param = str_replace('.', '_', $key);
                if (is_string($value)) {
                    $value = trim($value, '"');
                }
                if ($value == 'null') {
                    $qb->andWhere($qb->expr()->isNull($field));
                } elseif ($value == 'not_null') {
                    $qb->andWhere($qb->expr()->isNotNull($field));
                } else {
                    $qb->andWhere(sprintf('%s = :%s', $field, $param));
                    $qb->setParameter($param, $value);
                }
            }
        }
        if ($search) {
            $orX = $qb->expr()->orX();
            if (empty($this->getSearchableColumns())) {
                throw new \Exception('No searchable fields defined for ' . static::ENTITY);
            }
            foreach ($this->getSearchableColumns() as $column) {
                $orX->add($qb->expr()->like(sprintf('%s', $column), ':search'));
            }
            $qb->setParameter(':search', $search);
            $qb->andWhere($orX);
        }

        if (count($idsToExclude)) {
            $qb->andWhere($qb->expr()->notIn('a.id', $idsToExclude));
        }
        if (count($idsToInclude)) {
            $qb->andWhere($qb->expr()->in('a.id', $idsToInclude));
        }
        $qb->setFirstResult($offset)
            ->setMaxResults($limit);
        if ($order) {
            $orderField = str_contains($order['orderBy'], '.') ? $order['orderBy'] : 'a.' . $order['orderBy'];
            $qb->addOrderBy($orderField, $order['order']);
        }
        $query = $qb->getQuery();
        $columnCountData = [];
        foreach ($query->getResult() as $entity) {
//            $model = static::FACTORY::make($entity, [], $this->entityManager);
            $items[] = static::FACTORY::formatForRead($entity);
            if(!empty($this->getColumnsToCount())) {
                foreach($this->getColumnsToCount() as $columnToCount) {
//                    $methodName = sprintf('get%s', ucfirst($columnToCount));
                    if(property_exists($entity, $columnToCount)) {
                        if(isset($columnCountData['page'][$columnToCount])) {
                            $columnCountData['page'][$columnToCount] += $entity->{$columnToCount};
                            continue;
                        }
                        $columnCountData['page'][$columnToCount] = $entity->{$columnToCount};
                    }
                }
            }
        }
        if (!empty($this->getColumnsToCount())) {
            $sumQueryTotal = $this->entityManager->createQueryBuilder();
            $sumQueryTotal->select([]);
            $sumExpressions = [];
            foreach ($this->getColumnsToCount() as $columnToCount) {
                $sumExpressions[] = "SUM(a.$columnToCount) as $columnToCount";
            }

            $sumQueryTotal->select(implode(', ', $sumExpressions));
            $sumQueryTotal->from(static::ENTITY, 'a');
            $sumQueryResult = $sumQueryTotal->getQuery()->getSingleResult();

            if (!empty($sumQueryResult)) {
                $columnCountData['total'] = $sumQueryResult;
            }
        }
        return [
            'items' => $items,
            'countColumnData' => $columnCountData
        ];
    }

    public function getTotalCount(array $uncountableFilter = [])
    {
        return $this->entityManager->getRepository(static::ENTITY)->count($uncountableFilter);
    }


    public function getColumnsToCount(): array
    {
        return [];
    }

    public function searchByField(array $searchFilter, array $andFilters = [], $limit = null, $order = null, $offset = null)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')
            ->from(static::ENTITY, 'a');
        foreach ($searchFilter as $key => $value) {
            $qb->andWhere($qb->expr()->like('a.' . $key, ':' . $key));
            $qb->setParameter($key, '%' . $value . '%');
        }
        foreach ($andFilters as $key => $value) {
            $qb->andWhere(sprintf('a.%s = :%s', $key, $key));
            $qb->setParameter($key, $value);
        }
        if ($limit) {
            $qb->setMaxResults($limit);
        }
        if ($order) {
            $qb->addOrderBy('a.' . key($order), current($order));
        }
        if ($offset) {
            $qb->setFirstResult($offset);
        }
        return $qb->getQuery()->getResult();
    }
}
