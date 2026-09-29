<?php
namespace Skeletor\Visitor\Listener;

use Skeletor\Core\Activity\Listener\Deleted;
use Skeletor\Core\Activity\Repository\ActivityRepository;
use Skeletor\Visitor\Mapper\Subscription;

class VisitorDeleted extends Deleted
{
    public function __construct(ActivityRepository $activity, private Subscription $subscription)
    {
        parent::__construct($activity);
    }

    public function __invoke(object $event): void
    {
        $this->deleteSubscriptionRelations($event->getData()['oldModel']);
        parent::__invoke($event);
    }

    private function deleteSubscriptionRelations($oldModel)
    {
        $this->subscription->deleteBy('visitorId', $oldModel->getId());
    }
}