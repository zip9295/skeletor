<?php

namespace Skeletor\Core\Collection;

class Iterator implements \Iterator
{
    protected int $position = 0;

    public function __construct(protected Collection $collection, protected bool $reverse = false)
    {
    }

    public function current(): mixed
    {
        return $this->collection->getItems()[$this->position];
    }

    public function next(): void
    {
        $this->position += $this->reverse ? -1 : 1;
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return $this->reverse ? $this->position >= 0 : $this->position < count($this->collection->getItems());
    }

    public function rewind(): void
    {
        $this->position = $this->reverse ? count($this->collection->getItems()) - 1 : 0;
    }
}