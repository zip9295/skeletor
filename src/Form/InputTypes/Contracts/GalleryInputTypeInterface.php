<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface GalleryInputTypeInterface extends InputTypeInterface
{
    public function getImageData(): array;
}