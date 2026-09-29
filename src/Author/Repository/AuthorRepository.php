<?php

namespace Skeletor\Author\Repository;

use Skeletor\Author\Entity\Author;
use Skeletor\Author\Factory\AuthorFactory;
use Skeletor\Core\TableView\Repository\TableViewRepository;

class AuthorRepository extends TableViewRepository
{
    const ENTITY = Author::class;

    const FACTORY = AuthorFactory::class;

    public function getSearchableColumns(): array
    {
        return ['a.firstName', 'a.lastName', 'a.displayName'];
    }

    /**
     * @return Author[]
     */
    public function getActive(): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')
            ->from(Author::class, 'a')
            ->where('a.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('a.lastName', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
