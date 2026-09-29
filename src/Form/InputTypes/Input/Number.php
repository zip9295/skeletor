<?php

namespace Skeletor\Form\InputTypes\Input;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\NumberInputTypeInterface;
use Skeletor\Form\InputTypes\Validation\NumberValidation;
use Skeletor\Form\InputTypes\Validation\RequiredValidation;

class Number extends BaseInputType implements NumberInputTypeInterface
{
    use RequiredValidation;
    use NumberValidation;

    public function __construct(
        protected string $name,
        protected mixed $value,
        protected ?string $label = null,
        protected ?string $placeholder = null,
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