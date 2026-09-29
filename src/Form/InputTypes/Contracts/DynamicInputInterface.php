<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface DynamicInputInterface
{
    public function getName(): string;

    public function getType(): string;

    public function getLabel(): ?string;
}