<?php

namespace Skeletor\Form\InputTypes\TextArea;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\TextAreaInputTypeInterface;
use Skeletor\Form\InputTypes\Validation\RequiredValidation;
use Skeletor\Form\InputTypes\Validation\TextValidation;

class TextArea extends BaseInputType implements TextAreaInputTypeInterface
{

    use RequiredValidation;
    use TextValidation;
    public function __construct(
        protected string $name,
        protected mixed $value,
        protected ?string $label = null,
        protected int $cols = 10,
        protected int $rows = 5,
        protected ?string $placeholder = null,
        protected array $classList = [],
        protected ?string $id = null,
        protected ?string $tooltip = null,
        protected bool $readOnly = false
    )
    {
        parent::__construct($name, $label, $classList, $id, $tooltip, $readOnly);
    }

    public function getCols(): int
    {
        return $this->cols;
    }

    public function getRows(): int
    {
        return $this->rows;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}