<?php

namespace Skeletor\Core\Cache\Service;

use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class ObjectCache
{
    // @todo add centralized base key per app
    public function __construct(public TagAwareAdapter $adapter) {

    }
}