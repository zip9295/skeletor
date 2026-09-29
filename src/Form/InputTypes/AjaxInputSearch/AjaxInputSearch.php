<?php

namespace Skeletor\Form\InputTypes\AjaxInputSearch;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\AjaxInputSearchInputTypeInterface;
use Skeletor\Form\InputTypes\Validation\RequiredValidation;

class AjaxInputSearch extends BaseInputType implements AjaxInputSearchInputTypeInterface
{

    use RequiredValidation;
    public function __construct(
        protected string $name,
        protected string $endpoint,
        protected string $viewColumnName,
        protected string $idColumnName = 'id',
        protected ?string $label = null,
        protected mixed $value = null,
        protected ?string $viewValue = null,
        protected ?string $placeholder = null,
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

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getViewValue(): ?string
    {
        return $this->viewValue;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

}