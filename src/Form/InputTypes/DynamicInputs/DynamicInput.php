<?php

namespace Skeletor\Form\InputTypes\DynamicInputs;

use Skeletor\Form\InputTypes\Contracts\DynamicInputInterface;

class DynamicInput implements DynamicInputInterface
{
    public function __construct(
        protected string $name = '',
        protected string $type = 'text',
        protected ?string $label = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }
}