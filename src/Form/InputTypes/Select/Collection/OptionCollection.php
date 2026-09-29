<?php

namespace Skeletor\Form\InputTypes\Select\Collection;

use Skeletor\Core\Collection\Collection;
use Skeletor\Form\InputTypes\Contracts\OptionCollectionInterface;
use Skeletor\Form\InputTypes\Contracts\OptionInterface;
use Skeletor\Form\InputTypes\Select\Option;

class OptionCollection extends Collection implements OptionCollectionInterface
{
    public function __construct(
        protected ?OptionInterface $defaultValue = new Option('-1', '---')
    )
    {
        if($defaultValue !== null) {
            $this->items[] = $defaultValue;
        }
    }

    public function add(OptionInterface $option): void
    {
        $this->items[] = $option;
    }

    public function fromArray(array $options, null|int|string|array $selectedValue = null): static
    {
        foreach ($options as $value => $text) {
            if($value == $this->defaultValue->getValue()) {
                continue;
            }
            if(is_array($selectedValue)) {
                $selected = in_array($value, $selectedValue);
            } else {
                $selected = $value === $selectedValue;
            }
            $this->add(new Option($value, $text, $selected));
        }

        return $this;
    }

    public function getDefaultOption(): ?OptionInterface
    {
        return $this->defaultValue;
    }
}