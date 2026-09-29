<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface InputTypeCollectionInterface
{
    public function add(InputTypeInterface $input): void;
}