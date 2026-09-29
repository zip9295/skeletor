<?php
namespace Skeletor\Visitor\Listener;

use Skeletor\Core\Activity\Listener\Created;
use Skeletor\Core\Activity\Repository\ActivityRepository;
use Skeletor\Visitor\Mapper\Subscription;

class VisitorCreated extends Created
{
    public function __construct(
        ActivityRepository $activity, private Subscription $subscription, private \DateTime $dt,
        private \Skeletor\Subscription\Mapper\Subscription $subscriptionMapper)
    {
        parent::__construct($activity);
    }

    public function __invoke(object $event): void
    {
        $this->saveSubscriptions($event->getData()['data']['subscriptions'], $event->getData()['newModel']->getId());
        parent::__invoke($event);
    }

    private function saveSubscriptions($subscriptions, $visitorId)
    {
        foreach ($subscriptions as $subId) {
            $dt = clone $this->dt;
            $expires = null;
            $sub = $this->subscriptionMapper->fetchById($subId);
            if((int)$subId !== 10) {
                $expireDt = match ($sub['paymentPeriod']) {
                    \Skeletor\Subscription\Model\Subscription::PERIOD_MONTHLY => $dt->modify('+1 month'),
                    \Skeletor\Subscription\Model\Subscription::PERIOD_YEARLY => $dt->modify('+1 year'),
                    \Skeletor\Subscription\Model\Subscription::PERIOD_ONE_TIME => null,
                };
                if($expireDt) {
                    $expires = $expireDt->format('Y-m-d H:i:s');
                }
            }
            $this->subscription->insert([
                'visitorId' => $visitorId,
                'subscriptionId' => $subId,
                'expiresAt' => $expires,
            ]);
        }
    }
}