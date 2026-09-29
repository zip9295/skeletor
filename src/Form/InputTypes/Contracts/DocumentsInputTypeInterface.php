<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface DocumentsInputTypeInterface extends InputTypeInterface
{
    public function getDocumentData(): array;
}