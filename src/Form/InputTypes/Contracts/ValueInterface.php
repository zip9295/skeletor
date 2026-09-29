<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface ValueInterface
{
    public function getValue(): mixed;

    public function getText(): string;
}