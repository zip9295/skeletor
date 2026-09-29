<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface NumberInputTypeInterface extends InputTypeInterface
{
    public function getValue(): mixed;
}