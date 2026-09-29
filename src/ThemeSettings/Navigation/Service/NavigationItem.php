<?php

namespace Skeletor\ThemeSettings\Navigation\Service;

use Skeletor\Core\Service\CrudService;

class NavigationItem extends CrudService
{
    public function __construct(
        \Skeletor\ThemeSettings\Navigation\Repository\NavigationItemRepository $repo,
        \Skeletor\User\Service\Session $loginService,
        \Psr\Log\LoggerInterface $logger,
        \Skeletor\Core\Filter\FilterInterface $filter,
        \Skeletor\Core\Activity\Service\Activity $activity) {
        parent::__construct($repo, $loginService, $logger, null, $filter, activity: $activity);
    }
}