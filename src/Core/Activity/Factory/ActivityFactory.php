<?php

namespace Skeletor\Core\Activity\Factory;

use Skeletor\Core\Factory\AbstractFactory;

/**
 * Activity rows are written by ActivityRepository::record() rather than through the generic
 * create/update path, so nothing needs overriding here. The class exists because CrudRepository
 * resolves static::FACTORY for its read methods.
 */
class ActivityFactory extends AbstractFactory
{
}
