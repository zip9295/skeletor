<?php

namespace Skeletor\Form\InputTypes\DynamicInputs;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\DynamicInputCollectionInterface;
use Skeletor\Form\InputTypes\Contracts\DynamicInputsInputTypeInterface;

class DynamicInputs extends BaseInputType implements DynamicInputsInputTypeInterface
{
    public function __construct(
        protected DynamicInputCollectionInterface $inputs,
        protected string $name,
        protected array $values = [], //@todo implement with objects and not array
        protected string $addButtonLabel = 'Add',
        protected ?string $label = null,
        protected array $classList = [],
        protected ?string $id = null,
        protected ?string $tooltip = null,
        protected bool $readOnly = false
    ) {
        parent::__construct($name, $label, $classList, $id, $tooltip, $readOnly);
    }

    public function getInputCollection(): DynamicInputCollectionInterface
    {
        return $this->inputs;
    }

    public function getAddButtonLabel(): string
    {
        return $this->addButtonLabel;
    }

    public function getValues(): array
    {
        return $this->values;
    }

}