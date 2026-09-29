<?php

namespace Skeletor\ThemeSettings\Navigation\Service;

use Skeletor\Core\Cache\Service\ObjectCache;
use Skeletor\Core\Service\CrudService;
use Skeletor\ThemeSettings\Navigation\Repository\NavigationItemRepository;
use Symfony\Contracts\Cache\ItemInterface;

class Navigation extends CrudService
{
    const CACHE_KEY = 'navigation_';

    public function __construct(
        \Skeletor\ThemeSettings\Navigation\Repository\NavigationRepository $repo,
        \Skeletor\User\Service\Session $loginService,
        \Psr\Log\LoggerInterface $logger,
        \Skeletor\ThemeSettings\Navigation\Filter\Navigation $filter,
        protected NavigationItemRepository $navigationItemRepository,
        private ObjectCache $cache,
        \Skeletor\Core\Activity\Service\Activity $activity) {
        parent::__construct($repo, $loginService, $logger, null, $filter, activity: $activity);
    }

    public function getByTitle($title)
    {
        $cacheKey = static::CACHE_KEY . str_replace(' ', '', $title);

        $cachedData = $this->cache->adapter->get($cacheKey, function (ItemInterface $item) use ($title, $cacheKey) {
            $item->tag([$cacheKey]);
            $item->expiresAfter(null);
            $navigation = $this->getEntities(['label' => $title]);
            if(empty($navigation)) {
                // @TODO if null it needs to be invalidated ?
                $navigation = null;
            } else {
                $navigation = $navigation[0];
            }

            return serialize($navigation);
        });

        return unserialize($cachedData);
    }

    public function save($navigationId, array $items)
    {
        $navigation = $this->getById($navigationId);
        if($navigation) {
            $this->repo->deleteItems($navigationId);
            foreach($items as $position => $item) {
                $navItem = $this->navigationItemRepository->create([
                    'navigationId' => $navigationId,
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'position' => (int)$position + 1,
                    'openInNewTab' => $item['openInNewTab'] ?? 0,
                    'icon' => $item['icon'] ?? null,
                    'parentId' => null
                ]);
                $this->saveSubItems($navigationId, $navItem->id, $item['children'] ?? []);
            }
            $this->cache->adapter->invalidateTags([static::CACHE_KEY . str_replace(' ', '', $navigation->label)]);
        }
    }

    protected function saveSubItems($navigationId, $parentId, $children): void
    {
        foreach($children as $position => $child) {
            $navItem = $this->navigationItemRepository->create([
                'navigationId' => $navigationId,
                'label' => $child['label'] ?? '',
                'url' => $child['url'] ?? '',
                'position' => (int)$position + 1,
                'openInNewTab' => $child['openInNewTab'] ?? 0,
                'parentId' => $parentId,
                'icon' => $child['icon'] ?? null,
            ]);
            $this->saveSubItems($navigationId, $navItem->id, $child['children'] ?? []);
        }
    }
}