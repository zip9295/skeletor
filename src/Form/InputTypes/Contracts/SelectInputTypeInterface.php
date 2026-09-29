<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface SelectInputTypeInterface extends InputTypeInterface
{
    public function getOptionsCollection(): OptionCollectionInterface;
}