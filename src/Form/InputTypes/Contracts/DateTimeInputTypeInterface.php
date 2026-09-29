<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface DateTimeInputTypeInterface extends InputTypeInterface
{
    public function getValue(): mixed;
}