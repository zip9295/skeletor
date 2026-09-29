<?php

namespace Skeletor\Attribute\Event;

use League\Event\HasEventName;

class Attribute implements HasEventName
{
    const UPDATED = 'Attribute.updated';
    const CREATED = 'Attribute.created';
    const DELETED = 'Attribute.deleted';

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