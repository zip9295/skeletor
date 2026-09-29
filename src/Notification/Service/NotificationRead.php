<?php

namespace Skeletor\Notification\Service;

use Skeletor\Core\Service\ReadService;
use Skeletor\Notification\Repository\NotificationReadRepository;

class NotificationRead extends ReadService
{
    public function __construct(NotificationReadRepository $repo)
    {
        parent::__construct($repo);
    }


}