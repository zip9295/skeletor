<?php

namespace Skeletor\Form\InputTypes\Select;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\MultipleSelectInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\OptionCollectionInterface;
use Skeletor\Form\InputTypes\Validation\RequiredMultipleSelectValidation;

class MultipleSelect extends BaseInputType implements MultipleSelectInputTypeInterface
{
    use RequiredMultipleSelectValidation;
    public function __construct(
        protected string $name,
        protected OptionCollectionInterface $optionsCollection,
        protected ?string $label = null,
        protected array $classList = [],
        protected ?string $id = null,
        protected ?string $tooltip = null
    )
    {
        parent::__construct($name, $label, $classList, $id, $tooltip);
    }

    public function getOptionsCollection(): OptionCollectionInterface
    {
        return $this->optionsCollection;
    }
}