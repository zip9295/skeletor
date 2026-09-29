<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface TimeInputTypeInterface extends InputTypeInterface
{
    public function getValue(): mixed;
}