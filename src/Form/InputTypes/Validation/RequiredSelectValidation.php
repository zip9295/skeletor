<?php

namespace Skeletor\Form\InputTypes\Validation;

use Skeletor\Form\Attribute\Attribute;

trait RequiredSelectValidation
{
    public function required(string $message, int|string $emptyValue, bool $condition = true): static
    {
        if (!$condition) {
            return $this;
        }
        $this->addAttribute(new Attribute('data-required', 'true'));
        $this->addAttribute(new Attribute('data-required-text', $message));
        $this->addAttribute(new Attribute('data-select-empty-value', $emptyValue));
        return $this;
    }
}