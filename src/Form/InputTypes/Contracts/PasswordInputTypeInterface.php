<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface PasswordInputTypeInterface extends InputTypeInterface
{
    public function getPlaceholder(): ?string;

    public function getValue(): mixed;
}