<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface AjaxMultipleValuesSearchInputTypeInterface
{
    public function getEndpoint(): string;

    public function getViewColumnName(): string;

    public function getIdColumnName(): string;

    public function getFilters(): array;

    public function getFiltersJSON(): string;

    public function getValuesCollection(): ?ValuesCollectionInterface;

    public function getSearchPlaceholder(): string;

    public function getSearchValuesPlaceholder(): string;

    public function getValuesKey(): string;

    public function getCreatedValuesKey(): ?string;
}