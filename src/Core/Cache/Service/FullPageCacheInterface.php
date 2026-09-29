<?php
namespace Skeletor\Core\Cache\Service;

interface FullPageCacheInterface
{
    public function resetCache();

    public function deleteBySlug($item);

    public function getCachedItems();
}