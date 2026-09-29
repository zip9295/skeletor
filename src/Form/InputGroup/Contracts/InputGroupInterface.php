<?php

namespace Skeletor\Form\InputGroup\Contracts;

use Skeletor\Form\InputGroup\InputGroupWidth;
use Skeletor\Form\InputTypes\Contracts\InputTypeCollectionInterface;
use Skeletor\Form\InputTypes\Contracts\InputTypeInterface;

interface InputGroupInterface
{
    public function addInput(InputTypeInterface $input): InputGroupInterface;

    public function getInputCollection(): InputTypeCollectionInterface;

    public function getLabel(): ?string;

    public function getWidth(): ?InputGroupWidth;

}