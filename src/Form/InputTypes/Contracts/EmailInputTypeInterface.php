<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface EmailInputTypeInterface extends InputTypeInterface
{
    public function getPlaceholder(): ?string;

    public function getValue(): mixed;
}