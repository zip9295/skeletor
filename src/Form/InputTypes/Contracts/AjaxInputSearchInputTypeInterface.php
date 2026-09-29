<?php

namespace Skeletor\Form\InputTypes\Contracts;

interface AjaxInputSearchInputTypeInterface extends InputTypeInterface
{
    public function getEndpoint(): string;

    public function getViewColumnName(): string;

    public function getIdColumnName(): string;

    public function getFilters(): array;

    public function getFiltersJSON(): string;

    public function getValue(): mixed;

    public function getViewValue(): ?string;

    public function getPlaceholder(): ?string;
}