<?php
namespace Skeletor\Visitor\Listener;

use Skeletor\Core\Activity\Listener\Updated;
use Skeletor\Core\Activity\Repository\ActivityRepository;
use Skeletor\Visitor\Mapper\Subscription;
use Skeletor\Visitor\Model\Visitor;

class VisitorUpdated extends Updated
{
    public function __construct(ActivityRepository $activity, private Subscription $subscription, private \DateTime $dt,
        private \Skeletor\Subscription\Mapper\Subscription $subscriptionMapper) {
        parent::__construct($activity);
    }

    public function __invoke(object $event): void
    {
        $this->updateSubscriptions($event->getData()['data'], $event->getData()['oldModel']);
        parent::__invoke($event);
    }

    private function updateSubscriptions($postData, Visitor $oldModel)
    {
        $oldSubs = $newSubs = [];
        foreach ($oldModel->getSubscriptions() as $sub) {
            $oldSubs[] = $sub['subscriptionId'];
        }
        foreach ($postData['subscriptions'] as $subId) {
            $newSubs[] = $subId;
        }
        foreach (array_diff($oldSubs, $newSubs) as $subId) {
            $this->subscription->deleteByVisitorAndSubscription($oldModel->getId(), $subId);
        }
        foreach (array_diff($newSubs, $oldSubs) as $subId) {
            $sub = $this->subscriptionMapper->fetchById($subId);
            $dt = clone $this->dt;
            $expires = null;
            try {
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
                    'visitorId' => $oldModel->getId(),
                    'subscriptionId' => $subId,
                    'expiresAt' => $expires,
                ]);
            } catch(\UnhandledMatchError $e) {
                var_dump($e->getMessage());
            }
        }
    }
}