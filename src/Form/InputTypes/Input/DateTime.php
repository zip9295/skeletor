<?php

namespace Skeletor\Form\InputTypes\Input;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\DateTimeInputTypeInterface;
use Skeletor\Form\InputTypes\Validation\DateTimeValidation;
use Skeletor\Form\InputTypes\Validation\RequiredValidation;

class DateTime extends BaseInputType implements DateTimeInputTypeInterface
{
    use RequiredValidation;
    use DateTimeValidation;

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