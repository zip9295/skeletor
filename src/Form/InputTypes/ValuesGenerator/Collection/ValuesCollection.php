<?php

namespace Skeletor\Form\InputTypes\ValuesGenerator\Collection;

use Skeletor\Core\Collection\Collection;
use Skeletor\Form\InputTypes\Contracts\ValuesCollectionInterface;
use Skeletor\Form\InputTypes\Contracts\ValueInterface;
use Skeletor\Form\InputTypes\ValuesGenerator\Value;

class ValuesCollection extends Collection implements ValuesCollectionInterface
{

    public function add(ValueInterface $value): void
    {
        $this->items[] = $value;
    }

    public function fromArray(array $values): static
    {
        foreach ($values as $value => $text) {
            $this->add(new Value($value, $text));
        }

        return $this;
    }
}