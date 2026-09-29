<?php

namespace Skeletor\Form\InputTypes\Validation;

use Skeletor\Form\Attribute\Attribute;

trait MatchInputValidation
{
    public function matches(string $id, string $message, bool $condition = true): static
    {
        if (!$condition) {
            return $this;
        }
        $this->addAttribute(new Attribute('data-match-input-id', $id));
        $this->addAttribute(new Attribute('data-match-input-message', $message));
        return $this;
    }
}