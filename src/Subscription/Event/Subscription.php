<?php

namespace Skeletor\Subscription\Event;

use League\Event\HasEventName;

class Subscription implements HasEventName
{
    const UPDATED = 'subscription.updated';
    const CREATED = 'subscription.created';
    const DELETED = 'subscription.deleted';

    public function __construct(private string $name, private array $data) {}

    public function eventName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

}