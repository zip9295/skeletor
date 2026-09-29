<?php

namespace Skeletor\Form\InputTypes\Input;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\PasswordInputTypeInterface;
use Skeletor\Form\InputTypes\Validation\MatchInputValidation;
use Skeletor\Form\InputTypes\Validation\RegexValidation;
use Skeletor\Form\InputTypes\Validation\RequiredValidation;
use Skeletor\Form\InputTypes\Validation\TextValidation;

class Password extends BaseInputType implements PasswordInputTypeInterface
{

    use RequiredValidation;
    use TextValidation;
    use MatchInputValidation;
    use RegexValidation;

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

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}