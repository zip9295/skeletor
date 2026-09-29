<?php

namespace Skeletor\Form\InputGroup\Contracts;

interface InputGroupCollectionInterface
{
    public function add(InputGroupInterface $inputGroup): void;
}