<?php

namespace Skeletor\Form\Tab\Collection;

use Skeletor\Core\Collection\Collection;
use Skeletor\Form\Tab\Contracts\TabCollectionInterface;
use Skeletor\Form\Tab\Contracts\TabInterface;

class TabCollection extends Collection implements TabCollectionInterface
{
    public function add(TabInterface $tab): void
    {
        $this->items[] = $tab;
    }
}