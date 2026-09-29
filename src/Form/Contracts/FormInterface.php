<?php

namespace Skeletor\Form\Contracts;

use Skeletor\Form\InputGroup\Contracts\InputGroupCollectionInterface;
use Skeletor\Form\InputGroup\Contracts\InputGroupInterface;

interface FormInterface extends FormDataInterface
{
    public function addInputGroup(InputGroupInterface $inputGroup): InputGroupInterface;

    public function getInputGroupCollection(): InputGroupCollectionInterface;
}