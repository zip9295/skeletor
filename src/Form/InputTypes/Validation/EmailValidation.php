<?php

namespace Skeletor\Form\InputTypes\Validation;

use Skeletor\Form\Attribute\Attribute;

trait EmailValidation
{
    public function emailInvalidMessage(string $message, bool $condition = true): static
    {
        if (!$condition) {
            return $this;
        }
        $this->addAttribute(new Attribute('data-validation-strategy', 'email'));
        $this->addAttribute(new Attribute('data-validation-strategy-message', $message));
        return $this;
    }
}