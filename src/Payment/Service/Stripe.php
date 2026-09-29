<?php
namespace Skeletor\Payment\Service;

use Psr\Log\LoggerInterface;
use Skeletor\Visitor\Model\Visitor;
use Skeletor\Subscription\Service\Subscription;
use Stripe\StripeClient;
use Laminas\Session\ManagerInterface;

class Stripe
{
    public function __construct(
        private StripeClient $stripe, private \DateTime $dt, private Subscription $subscription,
        private \Skeletor\Visitor\Mapper\Subscription $visitorSubscription, private ManagerInterface $session,
        private \Skeletor\Visitor\Service\Visitor $visitorService, private LoggerInterface $logger
    ) { }

    public function cancelSubscription(Visitor $visitor, $subscriptionRemoteId)
    {
        foreach ($this->getSubscriptions($visitor) as $stripeSub) {
            if ($stripeSub['remoteId'] === $subscriptionRemoteId) {
                $response = $this->stripe->subscriptions->cancel($stripeSub['id'], [
                    'cancellation_details' => [
                        'comment' => 'cancelled by user request',
//                'feedback' => '',
                    ]
                ]);
                $sub = $this->subscription->getEntities(['remoteId' => $subscriptionRemoteId])[0];
                $visitorSub = $this->visitorSubscription->fetchAll(['subscriptionId' => $sub->getId(), 'visitorId' => $visitor->getId()]);
                $this->visitorSubscription->updateField('meta', 'User cancelled manually.', $sub->getId());

                return true;
            }
        }
        return false;
    }

    /**
     * Returns redirect url
     *
     * @return string|null
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createCheckout($subscriptionId): string
    {
        $userId = $this->session->getStorage()->offsetGet('loggedIn');
        if (!$userId) {
            return BASE_URL . '/login/loginForm';
        }
        $visitor = $this->visitorService->getById($userId);
        foreach($visitor->getSubscriptions() as $visitorSub) {
            if($visitorSub['subscriptionId'] === (int)$subscriptionId) {
                return BASE_URL . '/my-profile';
            }
        }
        $price = $this->stripe->products->retrieve($this->subscription->getById($subscriptionId)->getRemoteId());
        $checkoutSession = $this->stripe->checkout->sessions->create([
            'success_url' => BASE_URL . '/payment/success',
            'cancel_url' => BASE_URL . '/payment/fail',
            'line_items' => [[
                'price' => $price->default_price,
                'quantity' => 1
            ]],
            'mode' => 'subscription',
            'customer_email' => $this->session->getStorage()->offsetGet('loggedInEmail'),
        ]);
        $this->session->getStorage()->offsetSet('checkoutId', $checkoutSession->id);
        $this->session->getStorage()->offsetSet('subscriptionId', $subscriptionId);

        return $checkoutSession->url;
    }

    public function verifyCheckoutSession()
    {
        if (!$this->session->getStorage()->offsetGet('checkoutId')) {
            return false;
        }
        $session = $this->stripe->checkout->sessions->retrieve($this->session->getStorage()->offsetGet('checkoutId'));
        $subscription = $this->subscription->getById($this->session->getStorage()->offsetGet('subscriptionId'));
        $dt = clone $this->dt;
        $subData = [
            'visitorId' => $this->session->getStorage()->offsetGet('loggedIn'),
            'subscriptionId' => $this->session->getStorage()->offsetGet('subscriptionId'),
        ];
        if ($subscription->getPaymentPeriod() === \Skeletor\Subscription\Model\Subscription::PERIOD_MONTHLY) {
            $dt->modify('+1 month');
            $subData['expiresAt'] = $dt->format('Y-m-d H:i:s');
        } else if ($subscription->getPaymentPeriod() === \Skeletor\Subscription\Model\Subscription::PERIOD_YEARLY) {
            $dt->modify('+1 year');
            $subData['expiresAt'] = $dt->format('Y-m-d H:i:s');
        }
        if ($session->payment_status === 'paid' && $session->status === 'complete') {
            $this->visitorSubscription->insert($subData);
            $this->session->getStorage()->offsetUnset('subscriptionId');
            $this->session->getStorage()->offsetUnset('checkoutId');
            return true;
        }
        $this->resetCheckoutSession();

        return false;
    }

    public function resetCheckoutSession()
    {
        $this->stripe->checkout->sessions->expire($this->session->getStorage()->offsetGet('checkoutId'));
        $this->session->getStorage()->offsetUnset('checkoutId');
    }

    public function syncStripeSubscriptions(Visitor $visitor)
    {
        $localSubs = [];
        foreach ($this->visitorSubscription->fetchAll(['visitorId' => $visitor->getId()]) as $localSub) {
            // ignore free sub
            if ($localSub['subscriptionId'] === 10) {
                continue;
            }
            $localSubs[] = $localSub;
        }
        try {
            $stripeSubs = $this->getSubscriptions($visitor);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return;
        }
        if (count($stripeSubs) === 0) {
            $orphanedSubscriptions = [];
            foreach ($localSubs as $localSub) {
                // ignore free sub
                if ($localSub['subscriptionId'] === 10) {
                    continue;
                }

                // found locally but not on stripe
                // @TODO test our more, but should not be needed after all is cleaned up
//                $orphanedSubscriptions[] = $localSub;
            }
//            if (count($orphanedSubscriptions)) {
//                foreach ($orphanedSubscriptions as $orphan) {
//                    $this->visitorSubscription->delete($orphan['id']);
//                }
//            }
        }
        $insertedId = [];
        foreach ($stripeSubs as $subData) {
            $subFoundLocally = false;
            foreach ($localSubs as $sub) {
                $localSubscription = $this->subscription->getById($sub['subscriptionId']);
                if ($localSubscription->getRemoteId() === $subData['remoteId']) {
                    $subFoundLocally = true;
                    if ($subData['active'] && $sub['expiresAt'] && time() < $subData['expiresAt']->getTimestamp()) {
                        $this->visitorSubscription->updateField('expiresAt', $subData['expiresAt']->format('Y-m-d H:i:s'), $sub['id']);
                        $this->logger->info(sprintf('Extended subscription for visitor %s to %s', $visitor->getId(), $subData['expiresAt']->format('Y-m-d H:i:s')));
                    }
                }
            }
            if (!$subFoundLocally && !in_array($subData['remoteId'], $insertedId)) {
                $localSubscription = $this->subscription->getEntities(['remoteId' => $subData['remoteId']])[0];
                $insertedId[] = $subData['remoteId'];
                $this->visitorSubscription->insert([
                    'visitorId' => $visitor->getId(),
                    'meta' => $subData['name'],
                    'subscriptionId' => $localSubscription->getId(),
                    'expiresAt' => $subData['expiresAt']->format('Y-m-d H:i:s')
                ]);
                $this->logger->info(sprintf('Created missing subscription for visitor %s to %s', $visitor->getId(), $subData['expiresAt']->format('Y-m-d H:i:s')));
            }
        }
    }

    public function getProducts()
    {
        return $this->stripe->products->all()->data;
    }

    public function getSubscriptions(Visitor $visitor)
    {
        $data = $this->stripe->customers->all(['email' => $visitor->getEmail()])->data;
        if (!count($data)) {
            return [];
        }
        $data = $data[0];
        $subData = $this->stripe->subscriptions->all(['customer' => $data->id, 'status' => "all"])->data;
        $subs = [];
        foreach ($subData as $sub) {
            $dtEnd = clone $this->dt;
//            $dtEnd->modify('-1 day');
            $active = false;
            if ($sub->status === 'active') {
                $dtEnd->setTimestamp($sub->current_period_end);
                $active = true;
            }
            if ($sub->status === 'canceled') {
                $dtExpires = clone $this->dt;
                $dtExpires->setTimestamp($sub->current_period_end);
                if ($dtExpires > $this->dt) {
                    $active = true;
                }
            }
            $name = sprintf('%s - %s', $sub->plan->nickname, $sub->plan->amount / 100);
            if ($sub->discount) {
                $name .= " discounted";
            }
            $subs[] = [
                'id' => $sub->id,
                'name' => $name,
                'active' => $active,
                'remoteId' => $sub->plan->product,
                'expiresAt' => $dtEnd
            ];
        }
        return $subs;
    }

    public function createUser(Visitor $visitor)
    {
        // check requried params
        $stripeUser = $this->stripe->customers->create([
            'email' => $visitor->getEmail()
        ]);
        return $stripeUser->id;
    }
}