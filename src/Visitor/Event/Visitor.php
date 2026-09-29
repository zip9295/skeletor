<?php
namespace Skeletor\Visitor\Event;

class Visitor implements \League\Event\HasEventName
{
    const UPDATED = 'visitor.updated';
    const CREATED = 'visitor.created';
    const DELETED = 'visitor.deleted';

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