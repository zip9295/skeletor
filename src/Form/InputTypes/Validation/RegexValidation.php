<?php

namespace Skeletor\Form\InputTypes\Validation;

use Skeletor\Form\Attribute\Attribute;

trait RegexValidation
{
    public function matchesRegex(
        string $regex,
        string $message,
        bool $condition = true,
        bool $applyOnlyWhenPopulated = false
    ): static {
        if (!$condition) {
            return $this;
        }
        $this->addAttribute(new Attribute('data-validation-strategy', $regex));
        $this->addAttribute(new Attribute('data-validation-strategy-message', $message));
        if($applyOnlyWhenPopulated) {
            $this->addAttribute(new Attribute('data-apply-only-when-populated', $applyOnlyWhenPopulated));
        }
        return $this;
    }
}