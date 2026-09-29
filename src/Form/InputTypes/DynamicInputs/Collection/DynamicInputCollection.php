<?php

namespace Skeletor\Form\InputTypes\DynamicInputs\Collection;

use Skeletor\Core\Collection\Collection;
use Skeletor\Form\InputTypes\Contracts\DynamicInputCollectionInterface;
use Skeletor\Form\InputTypes\Contracts\DynamicInputInterface;

class DynamicInputCollection extends Collection implements DynamicInputCollectionInterface
{
    public function add(DynamicInputInterface $input): void
    {
        $this->items[] = $input;
    }
}