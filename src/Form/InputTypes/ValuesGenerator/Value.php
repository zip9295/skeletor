<?php

namespace Skeletor\Form\InputTypes\ValuesGenerator;

use Skeletor\Form\InputTypes\Contracts\ValueInterface;

class Value implements ValueInterface
{
    public function __construct(
        protected mixed $value,
        protected string $text,
    ) {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getText(): string
    {
        return $this->text;
    }
}