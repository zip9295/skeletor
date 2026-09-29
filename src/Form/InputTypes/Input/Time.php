<?php

namespace Skeletor\Form\InputTypes\Input;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\TimeInputTypeInterface;
use Skeletor\Form\InputTypes\Validation\RequiredValidation;
use Skeletor\Form\InputTypes\Validation\TimeValidation;

class Time extends BaseInputType implements TimeInputTypeInterface
{
    use RequiredValidation;
    use TimeValidation;

    public function __construct(
        protected string $name,
        protected mixed $value = null,
        protected ?string $label = null,
        protected array $classList = [],
        protected ?string $id = null,
        protected ?string $tooltip = null,
        protected bool $readOnly = false
    )
    {
        parent::__construct($name, $label, $classList, $id, $tooltip, $readOnly);
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}