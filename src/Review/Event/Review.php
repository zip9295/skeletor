<?php

namespace Skeletor\Review\Event;

use League\Event\HasEventName;

class Review implements HasEventName
{
    const UPDATED = 'review.updated';
    const CREATED = 'review.created';
    const DELETED = 'review.deleted';

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