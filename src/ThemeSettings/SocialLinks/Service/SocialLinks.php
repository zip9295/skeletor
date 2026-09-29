<?php

namespace Skeletor\ThemeSettings\SocialLinks\Service;

use Skeletor\Core\Cache\Service\ObjectCache;
use Skeletor\Core\Service\CrudService;
use Skeletor\ThemeSettings\Navigation\Repository\NavigationItemRepository;
use Skeletor\ThemeSettings\SocialLinks\Repository\SocialLinksRepository;
use Symfony\Contracts\Cache\ItemInterface;

class SocialLinks extends CrudService
{
    const CACHE_KEY = 'socialItems';

    public function __construct(
        SocialLinksRepository $repo,
        \Skeletor\User\Service\Session $loginService,
        \Psr\Log\LoggerInterface $logger,
        \Skeletor\ThemeSettings\SocialLinks\Filter\SocialLinks $filter,
        protected NavigationItemRepository $navigationItemRepository,
        private ObjectCache $cache,
        \Skeletor\Core\Activity\Service\Activity $activity) {
        parent::__construct($repo, $loginService, $logger, null, $filter, activity: $activity);
    }

    public function updateField($field, $value, $entityId)
    {
        $entity = parent::updateField($field, $value, $entityId);
        $this->cache->adapter->invalidateTags([static::CACHE_KEY]);

        return $entity;
    }

    public function getSocialItems()
    {
        $cacheKey = static::CACHE_KEY;

        $cachedData = $this->cache->adapter->get($cacheKey, function (ItemInterface $item) use ($cacheKey) {
            $item->tag([$cacheKey]);
            $item->expiresAfter(null);
            $items = $this->getEntities([], null, ['position' => 'ASC']);
//            if(empty($items)) {
//                // @TODO if null it needs to be invalidated ?
//                $items = null;
//            }

            return serialize($items);
        });

        return unserialize($cachedData);
    }

    public function getEntityByPlatform(string $platform): ?\Skeletor\ThemeSettings\SocialLinks\Entity\SocialLinks
    {
        return $this->repo->getEntityByPlatform($platform);
    }

}