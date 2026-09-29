<?php

namespace Skeletor\Core\Collection;

use Traversable;

class Collection implements \IteratorAggregate
{

    protected array $items = [];

    public function getIterator(): Traversable
    {
        return new Iterator($this);
    }

    public function getReverseIterator(): Traversable
    {
        return new Iterator($this, true);
    }

    public function getItems(): array
    {
        return $this->items;
    }
}