<?php

namespace Skeletor\Form\InputTypes\Validation;

use Skeletor\Form\Attribute\Attribute;

trait RequiredValidation
{
    public function required(string $message, bool $condition = true): static
    {
        if (!$condition) {
            return $this;
        }
        $this->addAttribute(new Attribute('data-required', 'true'));
        $this->addAttribute(new Attribute('data-required-text', $message));
        return $this;
    }
}