<?php

namespace Skeletor\Form\InputTypes\Select;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\SelectInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\OptionCollectionInterface;
use Skeletor\Form\InputTypes\Validation\RequiredSelectValidation;

class Select extends BaseInputType implements SelectInputTypeInterface
{
    use RequiredSelectValidation;

    public function __construct(
        protected string $name,
        protected OptionCollectionInterface $optionsCollection,
        protected ?string $label = null,
        protected array $classList = [],
        protected ?string $id = null,
        protected ?string $tooltip = null,
        protected bool $readOnly = false
    )
    {
        parent::__construct($name, $label, $classList, $id, $tooltip, $readOnly);
    }

    public function getOptionsCollection(): OptionCollectionInterface
    {
        return $this->optionsCollection;
    }
}