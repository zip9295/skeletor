<?php
namespace Skeletor\Subscription\Factory;

use Doctrine\ORM\EntityManagerInterface;

class SubscriptionFactory
{
    public static function compileEntityForUpdate($data, EntityManagerInterface $em)
    {
        $subscription = $em->getRepository(\Skeletor\Subscription\Entity\Subscription::class)->find($data['id']);
        $subscription->populateFromDto(new \Skeletor\Subscription\Model\Subscription(...$data));

        return $subscription->getId();
    }

    public static function compileEntityForCreate($data, EntityManagerInterface $em)
    {
        $subscription = new \Skeletor\Subscription\Entity\Subscription();
        $data['id'] = null;
        $subscription->populateFromDto(new \Skeletor\Subscription\Model\Subscription(...$data));
        $em->persist($subscription);

        return $subscription->getId();
    }

    public static function make($data, EntityManagerInterface $em): \Skeletor\Subscription\Model\Subscription
    {
        $data['price'] = $data['price'] / 1000;
        $blocks = [];
        if ($data['description'] === '') {
            $data['description'] = '[]';
        }
        foreach (json_decode($data['description']) as $block) {
            $blocks[] = (array) $block;
        }
        $data['blocks'] = $blocks;

        return new \Skeletor\Subscription\Model\Subscription(...$data);
    }
}