<?php

namespace Skeletor\Form\InputTypes\File;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\GalleryInputTypeInterface;

class Gallery extends BaseInputType implements GalleryInputTypeInterface
{
    public function __construct(
        protected string $name,
        protected ?string $label = null,
        protected array $imageData = [],
        protected array $classList = [],
        protected ?string $tooltip = null,
        protected bool $readOnly = false
    )
    {
        parent::__construct($name, $label, $classList, null, $tooltip, $readOnly);
        return $this;
    }

    public function getImageData(): array
    {
        return $this->imageData;
    }
}