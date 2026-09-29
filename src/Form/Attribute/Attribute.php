<?php

namespace Skeletor\Form\Attribute;

use Skeletor\Form\Attribute\Contracts\AttributeInterface;

class Attribute implements AttributeInterface
{

    public function __construct(public readonly string $key, public readonly int|string $value)
    {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): int|string
    {
        return $this->value;
    }
}