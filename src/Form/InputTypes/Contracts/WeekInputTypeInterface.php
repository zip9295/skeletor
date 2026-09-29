<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface WeekInputTypeInterface extends InputTypeInterface
{
    public function getValue(): mixed;
}