<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface ContentEditorInputTypeInterface extends InputTypeInterface
{
    public function getJsonContent(): string;

    public function getSearchBlocksPlaceholder(): string;
}