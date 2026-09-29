<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface ValuesGeneratorInputTypeInterface extends InputTypeInterface
{
    public function getValuesCollection(): ?ValuesCollectionInterface;

    public function getExistingValueKey(): string;

    public function getNewValueKey(): string;

    public function getPlaceholder(): ?string;

    public function getSearchPlaceholder(): ?string;
}