<?php

namespace Skeletor\ThemeSettings\SocialLinks\Repository;

use Skeletor\Core\Repository\CrudRepository;
use Skeletor\ThemeSettings\SocialLinks\Entity\SocialLinks;
use Skeletor\ThemeSettings\SocialLinks\Factory\SocialLinksFactory;

class SocialLinksRepository extends CrudRepository
{
    const ENTITY = SocialLinks::class;
    const FACTORY = SocialLinksFactory::class;

    public function getEntityByPlatform(string $platform): ?SocialLinks
    {
        $qb = $this->entityManager->createQueryBuilder('s');
        $qb->select('s')
            ->from(self::ENTITY, 's')
            ->where('s.platform = :platform')
            ->setParameter('platform', $platform);
        $res = $qb->getQuery()->getOneOrNullResult();
        return $res;
    }
}