<?php

namespace Skeletor\Form\Tab;

use Skeletor\Form\InputGroup\Collection\InputGroupCollection;
use Skeletor\Form\InputGroup\Contracts\InputGroupCollectionInterface;
use Skeletor\Form\InputGroup\Contracts\InputGroupInterface;
use Skeletor\Form\Tab\Contracts\TabInterface;

class Tab implements TabInterface
{
    public function __construct(
        protected string $label,
        protected InputGroupCollectionInterface $inputGroupCollection = new InputGroupCollection()
    )
    {
        return $this;
    }

    public function addInputGroup(InputGroupInterface $inputGroup): TabInterface
    {
        $this->inputGroupCollection->add($inputGroup);
        return $this;
    }

    public function getInputGroupCollection(): InputGroupCollectionInterface
    {
        return $this->inputGroupCollection;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}