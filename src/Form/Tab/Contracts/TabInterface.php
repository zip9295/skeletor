<?php

namespace Skeletor\Form\Tab\Contracts;

use Skeletor\Form\InputGroup\Contracts\InputGroupCollectionInterface;
use Skeletor\Form\InputGroup\Contracts\InputGroupInterface;

interface TabInterface
{
    public function addInputGroup(InputGroupInterface $inputGroup): TabInterface;

    public function getInputGroupCollection(): InputGroupCollectionInterface;

    public function getLabel(): string;
}