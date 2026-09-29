<?php

namespace Skeletor\Review\Listener;

use Skeletor\Core\Activity\Listener\Updated as ActivityListener;
use Skeletor\Core\Activity\Repository\ActivityRepository;

class ReviewUpdated extends ActivityListener
{
    public function __construct(ActivityRepository $activity)
    {
        parent::__construct($activity);
    }
}