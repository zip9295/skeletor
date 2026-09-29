<?php

namespace Skeletor\Form\InputTypes\File;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\ImageInputTypeInterface;
use Skeletor\Form\InputTypes\Validation\RequiredValidation;

class Image extends BaseInputType implements ImageInputTypeInterface
{
    use RequiredValidation;

    public function __construct(
        protected string $name,
        protected string $chooseImageText = 'Choose Image',
        protected ?string $label = null,
        protected ?string $src = null,
        protected null|string|int $imageId = null,
        protected ?string $id = null,
        protected array $classList = [],
        protected ?string $tooltip = null,
        protected bool $readOnly = false
    )
    {
        parent::__construct($name, $label, $classList, $id, $tooltip, $readOnly);
        return $this;
    }

    public function getImageId(): null|string|int
    {
        return $this->imageId;
    }

    public function getChooseImageText(): string
    {
        return $this->chooseImageText;
    }

    public function getSrc(): ?string
    {
        return $this->src;
    }
}