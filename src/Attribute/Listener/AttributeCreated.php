<?php

namespace Skeletor\Attribute\Listener;

use Skeletor\Core\Activity\Listener\Created as ActivityListener;
use Skeletor\Core\Activity\Repository\ActivityRepository;

class AttributeCreated extends ActivityListener
{
    public function __construct(ActivityRepository $activity)
    {
        parent::__construct($activity);
    }
}