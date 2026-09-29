<?php

namespace Skeletor\Form\InputTypes\Select;

use Skeletor\Form\InputTypes\Contracts\OptionInterface;

class Option implements OptionInterface
{
    public function __construct(
        protected string $value,
        protected string $text,
        protected bool $selected = false
    ) {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }
}