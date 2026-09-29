<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface TextAreaInputTypeInterface
{
    public function getCols(): int;
    public function getRows(): int;

    public function getPlaceholder(): ?string;

    public function getValue(): ?string;
}