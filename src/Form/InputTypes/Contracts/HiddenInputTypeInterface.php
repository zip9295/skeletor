<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface HiddenInputTypeInterface extends InputTypeInterface
{
    public function getValue(): mixed;
}