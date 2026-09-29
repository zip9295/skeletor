<?php

namespace Skeletor\Form\Attribute\Collection;

use Skeletor\Core\Collection\Collection;
use Skeletor\Form\Attribute\Contracts\AttributeCollectionInterface;
use Skeletor\Form\Attribute\Contracts\AttributeInterface;

class AttributeCollection extends Collection implements AttributeCollectionInterface
{
    public function add(AttributeInterface $attribute): void
    {
        $this->items[] = $attribute;
    }

    public function has(string $name, mixed $value = null): bool
    {
        foreach ($this->items as $item) {
            if($value !== null) {
                if($item->getKey() === $name && $item->getValue() === $value) {
                    return true;
                }
            } else {
                if($item->getKey() === $name) {
                    return true;
                }
            }
        }
        return false;
    }
}