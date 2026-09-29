<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface TextEditorInputTypeInterface extends InputTypeInterface
{
    public function getValue(): ?string;
}