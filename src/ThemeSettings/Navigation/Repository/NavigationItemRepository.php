<?php

namespace Skeletor\ThemeSettings\Navigation\Repository;

use Skeletor\Core\Repository\CrudRepository;
use Skeletor\ThemeSettings\Navigation\Entity\NavigationItem;
use Skeletor\ThemeSettings\Navigation\Factory\NavigationItemFactory;

class NavigationItemRepository extends CrudRepository
{
    const ENTITY = NavigationItem::class;
    const FACTORY = NavigationItemFactory::class;
}