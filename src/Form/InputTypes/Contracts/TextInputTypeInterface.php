<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface TextInputTypeInterface extends InputTypeInterface
{
    public function getPlaceholder(): ?string;

    public function getValue(): mixed;
}