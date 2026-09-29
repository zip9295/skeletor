<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface MonthInputTypeInterface extends InputTypeInterface
{
    public function getValue(): mixed;
}