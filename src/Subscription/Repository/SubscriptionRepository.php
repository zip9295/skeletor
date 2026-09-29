<?php
namespace Skeletor\Subscription\Repository;

use Doctrine\ORM\EntityManagerInterface;
use League\Event\EventDispatcher;
use Skeletor\Core\TableView\Repository\TableViewRepository;
use \Skeletor\Subscription\Event\Subscription as Event;

class SubscriptionRepository extends TableViewRepository
{
    const FACTORY = \Skeletor\Subscription\Factory\SubscriptionFactory::class;
    const ENTITY = \Skeletor\Subscription\Entity\Subscription::class;

    public function __construct(EntityManagerInterface $em, private \DateTime $dt)
    {
        parent::__construct($em);
    }

    public function getSearchableColumns(): array
    {
        return ['title'];
    }

    public function getCounts()
    {
        return [];
    }
}