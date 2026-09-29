<?php

namespace Skeletor\Form\InputTypes\Input;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\HiddenInputTypeInterface;

class Hidden extends BaseInputType implements HiddenInputTypeInterface
{
    public function __construct(protected string $name, protected mixed $value = null)
    {
        parent::__construct($name);
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}