<?php

namespace Skeletor\Form\Attribute\Contracts;

interface AttributeCollectionInterface
{
    public function add(AttributeInterface $attribute): void;

    public function has(string $name, mixed $value = null): bool;
}