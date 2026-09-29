<?php
namespace Skeletor\Subscription\Service;

use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\TableView\Service\TableView;
use \Skeletor\Subscription\Repository\SubscriptionRepository;
use Skeletor\User\Service\Session;
use Skeletor\Subscription\Filter\Subscription as Filter;

class Subscription extends TableView
{
    public function __construct(SubscriptionRepository $repository, Session $session, Logger $logger, Filter $filter,
        \Skeletor\Core\Activity\Service\Activity $activity)
    {
        parent::__construct($repository, $session, $logger, $filter, activity: $activity);
    }

    public function getEntityData($id)
    {
        $subscription = $this->repo->getById($id);
        return [
            'id' => $subscription->getId(),
            'title' => $subscription->getName(),
            'price' => $subscription->getPrice(),
            'isActive' => $subscription->getIsActive(),
            'createdAt' => $subscription->getUpdatedAt()->format('m.d.Y'),
            'updatedAt' => $subscription->getCreatedAt()->format('m.d.Y'),
        ];
    }

    public function prepareEntities($entities)
    {
//        $counts = $this->repo->getCounts();
//        $activeOldSubs = $inactiveOldSubs = 0;
//        $activeOldSubsFullInfo = $inactiveOldSubsFullInfo = '';
//        foreach ($counts['active'][9] as $meta => $count) {
//            $activeOldSubs += $count;
//            $activeOldSubsFullInfo .=
//                '<span style="display:block;color:#858796;">' .
//                $meta . '<span style="color:#1cc88a"> (' . $count . ')</span>' . '</span>';
//        }
//        $counts['active'][9] = $activeOldSubs;
//        foreach ($counts['inactive'][9] as $meta => $count) {
//            $inactiveOldSubs += $count;
//            $inactiveOldSubsFullInfo .=
//                '<span style="display:block;color:#858796;">' .
//                $meta . '<span style="color:#e74a3b"> (' . $count . ')</span>' . '</span>';
//        }
//        $counts['active'][9] = $activeOldSubs;
//        $counts['inactive'][9] = $inactiveOldSubs;
        $items = [];
        foreach ($entities as $subscription) {
            $itemData = [
                'id' => $subscription->getId(),
                'title' =>  [
                    'value' => $subscription->getTitle(),
                    'editColumn' => true,
                ],
                'price' => $subscription->getPrice(),
                'paymentPeriod' => $subscription->getHrPeriod($subscription->getPaymentPeriod()),
                'isActive' => ($subscription->getIsActive()) ? 'Yes':'No',
                'totalActive' => isset($counts['active'][$subscription->getId()]) ? $counts['active'][$subscription->getId()]:0,
                'totalInactive' => isset($counts['inactive'][$subscription->getId()]) ? $counts['inactive'][$subscription->getId()]:0,
                'createdAt' => $subscription->getCreatedAt()->format('d.m.Y'),
                'updatedAt' => $subscription->getCreatedAt()->format('d.m.Y'),
            ];
            $entryData = [
                'columns' => $itemData,
                'id' => $subscription->getId(),
            ];
            $items[] = $entryData;
        }
        return $items;
    }

    public function compileTableColumns()
    {
        $columnDefinitions = [
            ['name' => 'title', 'label' => 'Title'],
            ['name' => 'price', 'label' => 'Price'],
            ['name' => 'paymentPeriod', 'label' => 'Period'],
            ['name' => 'isActive', 'label' => 'Show'],
            ['name' => 'totalActive', 'label' => 'Active subs', 'sortable' => false],
            ['name' => 'totalInactive', 'label' => 'Inactive subs', 'sortable' => false],
            ['name' => 'createdAt', 'label' => 'Created at'],
            ['name' => 'updatedAt', 'label' => 'Updated at'],
        ];

        return $columnDefinitions;
    }

    public function getActiveSubscriptionPlans()
    {
        $subscriptions = [];
        $subscriptionEntities = $this->getEntities(['isActive' => 1]);
        foreach($subscriptionEntities as $subscriptionEntity) {
            $subscriptions[$subscriptionEntity->getId()] = $subscriptionEntity;
        }
        return $subscriptions;
    }

    public function orderSubsByType($subs)
    {
        $ordered = ['free' => [], 'monthly' => [], 'yearly' => [], 'once' => []];
        if($subs && is_array($subs)) {
            foreach($subs as $sub) {
                if($sub->getPrice() == 0) {
                    $ordered['free'][] = $sub;
                    continue;
                }
                switch($sub->getPaymentPeriod()) {
                    case \Skeletor\Subscription\Model\Subscription::PERIOD_MONTHLY:
                        $ordered['monthly'][] = $sub;
                        break;
                    case \Skeletor\Subscription\Model\Subscription::PERIOD_YEARLY:
                        $ordered['yearly'][] = $sub;
                        break;
                    case \Skeletor\Subscription\Model\Subscription::PERIOD_ONE_TIME:
                        $ordered['once'][] = $sub;
                        break;
                }
            }
        }
        return $ordered;
    }
}