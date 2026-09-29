<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface CheckboxInputTypeInterface extends InputTypeInterface
{
    public function isChecked(): bool;
}