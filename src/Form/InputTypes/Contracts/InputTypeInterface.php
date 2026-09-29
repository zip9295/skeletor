<?php

namespace Skeletor\Form\InputTypes\Contracts;

use Skeletor\Form\Attribute\Contracts\AttributeCollectionInterface;
use Skeletor\Form\Attribute\Contracts\AttributeInterface;

interface InputTypeInterface
{
    public function addAttribute(AttributeInterface $attribute): static;

    public function getAttributeCollection(): AttributeCollectionInterface;

    public function hasAttribute(string $name, mixed $value = null): bool;

    public function getClassList(): array;

    public function getName(): string;

    public function getLabel(): ?string;

    public function getTooltip(): ?string;

    public function getId(): ?string;

    public function isReadOnly(): bool;
}