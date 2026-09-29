<?php

namespace Skeletor\Form\InputTypes\Collection;

use Skeletor\Core\Collection\Collection;
use Skeletor\Form\InputTypes\Contracts\InputTypeCollectionInterface;
use Skeletor\Form\InputTypes\Contracts\InputTypeInterface;

class InputTypeCollection extends Collection implements InputTypeCollectionInterface
{
    public function add(InputTypeInterface $input): void
    {
        $this->items[] = $input;
    }
}