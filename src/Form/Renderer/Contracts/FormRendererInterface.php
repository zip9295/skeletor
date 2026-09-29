<?php

namespace Skeletor\Form\Renderer\Contracts;

use Skeletor\Form\InputGroup\Contracts\InputGroupInterface;
use Skeletor\Form\InputTypes\Contracts\InputTypeInterface;

interface FormRendererInterface
{
    public function render(): string;

    public function setContentBeforeInputGroup(InputGroupInterface $inputGroup, string $content): void;

    public function setContentBeforeInput(InputTypeInterface $input, string $content): void;

    public function setContentAfterInputGroup(InputGroupInterface $inputGroup, string $content): void;

    public function setContentAfterInput(InputTypeInterface $input, string $content): void;
}