<?php
namespace Skeletor\Visitor\Model;

use Skeletor\Core\Acl\AclInterface;
use Skeletor\Core\Model\Model;

class Subscription extends Model
{

    /**
     * @param int $id
     * @param \Skeletor\Subscription\Model\Subscription $subscription
     * @param \DateTime $expires
     * @param Visitor $visitor
     * @param \DateTime $createdAt
     * @param \DateTime $updatedAt
     */
    public function __construct(
        private int $id, private \Skeletor\Subscription\Model\Subscription $subscription, private \DateTime $expires,
        private Visitor $visitor, private \DateTime $createdAt, private \DateTime $updatedAt
    ) {
        parent::__construct($createdAt, $updatedAt);
    }

    /**
     * @return \Skeletor\Subscription\Model\Subscription
     */
    public function getSubscription(): \Skeletor\Subscription\Model\Subscription
    {
        return $this->subscription;
    }

    /**
     * @return \DateTime
     */
    public function getExpires(): \DateTime
    {
        return $this->expires;
    }

    /**
     * @return Visitor
     */
    public function getVisitor(): Visitor
    {
        return $this->visitor;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
