<?php

namespace Skeletor\Form\InputTypes\AjaxMultipleValuesSearch;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\AjaxMultipleValuesSearchInputTypeInterface;
use Skeletor\Form\InputTypes\Contracts\ValuesCollectionInterface;

class AjaxMultipleValuesSearch extends BaseInputType implements AjaxMultipleValuesSearchInputTypeInterface
{
    public function __construct(
        protected string $name,
        protected string $endpoint,
        protected string $viewColumnName,
        protected string $valuesKey = 'values',
        protected ?string $createdValuesKey = null,
        protected string $idColumnName = 'id',
        protected ?ValuesCollectionInterface $valuesCollection = null,
        protected ?string $label = null,
        protected string $searchPlaceholder = 'Search...',
        protected string $searchValuesPlaceholder = 'Search...',
        protected array $filters = [],
        protected array $classList = [],
        protected ?string $id = null,
        protected ?string $tooltip = null,
        protected bool $readOnly = false
    ) {
        parent::__construct($name, $label, $classList, $id, $tooltip, $readOnly);
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getViewColumnName(): string
    {
        return $this->viewColumnName;
    }

    public function getIdColumnName(): string
    {
        return $this->idColumnName;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getFiltersJSON(): string
    {
        return json_encode($this->filters);
    }

    public function getValuesCollection(): ?ValuesCollectionInterface
    {
        return $this->valuesCollection;
    }

    public function getSearchValuesPlaceholder(): string
    {
        return $this->searchValuesPlaceholder;
    }

    public function getSearchPlaceholder(): string
    {
        return $this->searchPlaceholder;
    }

    public function getValuesKey(): string
    {
        return $this->valuesKey;
    }

    public function getCreatedValuesKey(): ?string
    {
        return $this->createdValuesKey;
    }



}