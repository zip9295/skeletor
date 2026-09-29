<?php

namespace Skeletor\Form\InputGroup;


use Skeletor\Form\InputGroup\Contracts\InputGroupInterface;
use Skeletor\Form\InputTypes\Collection\InputTypeCollection;
use Skeletor\Form\InputTypes\Contracts\InputTypeCollectionInterface;
use Skeletor\Form\InputTypes\Contracts\InputTypeInterface;

class InputGroup implements InputGroupInterface
{
    public function __construct(
        protected readonly ?string $label = null,
        protected readonly ?InputGroupWidth $width = null,
        protected InputTypeCollectionInterface $inputCollection = new InputTypeCollection()
    )
    {
        return $this;
    }

    public function addInput(InputTypeInterface $input): InputGroupInterface
    {
        $this->inputCollection->add($input);
        return $this;
    }

    public function getInputCollection(): InputTypeCollectionInterface
    {
        return $this->inputCollection;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getWidth(): ?InputGroupWidth
    {
        return $this->width;
    }
}