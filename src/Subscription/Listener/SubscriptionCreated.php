<?php

namespace Skeletor\Subscription\Listener;


use Skeletor\Core\Activity\Listener\Created;
use Skeletor\Core\Activity\Repository\ActivityRepository;

class SubscriptionCreated extends Created
{
    public function __construct(ActivityRepository $activity) {
        parent::__construct($activity);
    }
}