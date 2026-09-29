<?php

namespace Skeletor\Form\InputTypes\Input;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\CheckboxInputTypeInterface;
use Skeletor\Form\InputTypes\Validation\RequiredValidation;

class Checkbox extends BaseInputType implements CheckboxInputTypeInterface
{
    use RequiredValidation;
    public function __construct(
        protected string $name,
        protected bool $checked = false,
        protected ?string $label = null,
        protected array $classList = [],
        protected ?string $id = null,
        protected ?string $tooltip = null,
        protected bool $readOnly = false
    )
    {
        parent::__construct($name, $label, $classList, $id, $tooltip, $readOnly);
    }

    public function isChecked(): bool
    {
        return $this->checked;
    }
}