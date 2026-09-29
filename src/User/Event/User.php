<?php
namespace Skeletor\User\Event;

class User implements \League\Event\HasEventName
{
    const UPDATED = 'user.updated';
    const CREATED = 'user.created';
    const DELETED = 'user.deleted';

    /** @var string */
    private $name;

    /** @var array */
    private $data;

    public function __construct(string $name, array $data)
    {
        $this->name = $name;
        $this->data = $data;
    }

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