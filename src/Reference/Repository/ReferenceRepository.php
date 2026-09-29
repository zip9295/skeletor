<?php

namespace Skeletor\Reference\Repository;

use Skeletor\Reference\Entity\Reference;
use Skeletor\Reference\Factory\ReferenceFactory;
use Skeletor\Core\TableView\Repository\TableViewRepository;

class ReferenceRepository extends TableViewRepository
{
    const ENTITY = Reference::class;

    const FACTORY = ReferenceFactory::class;

    function getSearchableColumns(): array
    {
        return ['a.title', 'a.comment'];
    }

    public function getByIds(array $ids)
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('a')
            ->from(Reference::class, 'a')
            ->where($qb->expr()->in('a.id', ':ids'))
            ->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }
}