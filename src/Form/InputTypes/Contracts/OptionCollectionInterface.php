<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface OptionCollectionInterface
{
    public function add(OptionInterface $option): void;

    public function getDefaultOption(): ?OptionInterface;

    public function fromArray(array $options, null|int|string|array $selectedValue = null): static;
}