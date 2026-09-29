<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface ImageInputTypeInterface extends InputTypeInterface
{
    public function getImageId(): null|string|int;

    public function getSrc(): ?string;

    public function getChooseImageText(): string;

}