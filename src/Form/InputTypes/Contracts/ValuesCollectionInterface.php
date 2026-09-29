<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface ValuesCollectionInterface
{
    public function add(ValueInterface $value): void;

    public function fromArray(array $values): static;
}