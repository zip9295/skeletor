<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface DocumentInputTypeInterface extends InputTypeInterface
{
    public function getDocumentId(): null|int|string;

    public function getChooseDocumentText(): string;

    public function getFilename(): ?string;
}