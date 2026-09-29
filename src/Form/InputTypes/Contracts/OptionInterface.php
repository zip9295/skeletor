<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface OptionInterface
{
    public function getValue(): string;

    public function getText(): string;

    public function isSelected(): bool;
}