<?php

namespace Skeletor\Form\InputTypes\Base;

use Skeletor\Form\Attribute\Collection\AttributeCollection;
use Skeletor\Form\Attribute\Contracts\AttributeCollectionInterface;
use Skeletor\Form\Attribute\Contracts\AttributeInterface;
use Skeletor\Form\InputTypes\Contracts\InputTypeInterface;

abstract class BaseInputType implements InputTypeInterface
{
    public function __construct(
        protected string $name,
        protected ?string $label = null,
        protected array $classList = [],
        protected ?string $id = null,
        protected ?string $tooltip = null,
        protected bool $readOnly = false,
        protected AttributeCollectionInterface $attributeCollection = new AttributeCollection()
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getClassList(): array
    {
        return $this->classList;
    }

    public function addAttribute(AttributeInterface $attribute): static
    {
        $this->attributeCollection->add($attribute);
        return $this;
    }

    public function getAttributeCollection(): AttributeCollectionInterface
    {
        return $this->attributeCollection;
    }

    public function hasAttribute(string $name, mixed $value = null): bool
    {
        return $this->attributeCollection->has($name, $value);
    }

    public function getTooltip(): ?string
    {
        return $this->tooltip;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }
}