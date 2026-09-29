<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface DateInputTypeInterface extends InputTypeInterface
{
    public function getValue(): mixed;
}