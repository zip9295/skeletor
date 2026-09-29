<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface DynamicInputsInputTypeInterface extends InputTypeInterface
{
    public function getInputCollection(): DynamicInputCollectionInterface;
    public function getAddButtonLabel(): string;

    public function getValues(): array;


}