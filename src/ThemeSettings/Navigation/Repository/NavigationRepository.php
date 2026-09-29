<?php

namespace Skeletor\ThemeSettings\Navigation\Repository;

use Skeletor\Core\Repository\CrudRepository;
use Skeletor\ThemeSettings\Navigation\Entity\Navigation;
use Skeletor\ThemeSettings\Navigation\Factory\NavigationFactory;

class NavigationRepository extends CrudRepository
{
    const ENTITY = Navigation::class;
    const FACTORY = NavigationFactory::class;

    public function deleteItems($navigationId): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(\Skeletor\ThemeSettings\Navigation\Entity\NavigationItem::class, 'ni')
            ->where('ni.navigation = :navigationId')
            ->setParameter('navigationId', $navigationId)
            ->getQuery()
            ->execute();
    }
}