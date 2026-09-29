<?php

namespace Skeletor\Form\InputTypes\Input;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\DateInputTypeInterface;
use Skeletor\Form\InputTypes\Validation\DateTimeValidation;
use Skeletor\Form\InputTypes\Validation\DateValidation;
use Skeletor\Form\InputTypes\Validation\RequiredValidation;

class Date extends BaseInputType implements DateInputTypeInterface
{
    use RequiredValidation;
    use DateValidation;

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