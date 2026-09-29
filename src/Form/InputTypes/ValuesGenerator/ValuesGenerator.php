<?php

namespace Skeletor\Form\InputTypes\ValuesGenerator;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\ValuesCollectionInterface;
use Skeletor\Form\InputTypes\Contracts\ValuesGeneratorInputTypeInterface;

class ValuesGenerator extends BaseInputType implements ValuesGeneratorInputTypeInterface
{
    public function __construct(
        protected string $name,
        protected ?ValuesCollectionInterface $valuesCollection = null,
        protected string $existingValueKey = 'values',
        protected string $newValueKey = 'new',
        protected ?string $label = null,
        protected ?string $placeholder = null,
        protected ?string $searchPlaceholder = 'Search values',
        protected array $classList = [],
        protected ?string $id = null,
        protected ?string $tooltip = null,
        protected bool $readOnly = false
    )
    {
        parent::__construct($name, $label, $classList, $id, $tooltip, $readOnly);
    }

    public function getValuesCollection(): ?ValuesCollectionInterface
    {
        return $this->valuesCollection;
    }

    public function getExistingValueKey(): string
    {
        return $this->existingValueKey;
    }

    public function getNewValueKey(): string
    {
        return $this->newValueKey;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getSearchPlaceholder(): ?string
    {
        return $this->searchPlaceholder;
    }
}