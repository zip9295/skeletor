<?php

namespace Skeletor\Form\InputTypes\Contracts;


interface DynamicInputCollectionInterface
{
    public function add(DynamicInputInterface $input): void;
}