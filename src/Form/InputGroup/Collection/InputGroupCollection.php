<?php

namespace Skeletor\Form\InputGroup\Collection;

use Skeletor\Core\Collection\Collection;
use Skeletor\Form\InputGroup\Contracts\InputGroupCollectionInterface;
use Skeletor\Form\InputGroup\Contracts\InputGroupInterface;

class InputGroupCollection extends Collection implements InputGroupCollectionInterface
{
    public function add(InputGroupInterface $inputGroup): void
    {
        $this->items[] = $inputGroup;
    }
}