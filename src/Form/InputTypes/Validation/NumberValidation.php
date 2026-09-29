<?php

namespace Skeletor\Form\InputTypes\Validation;

use Skeletor\Form\Attribute\Attribute;

trait NumberValidation
{
    public function min(
        int $minLength,
        string $message,
        bool $condition = true,
        bool $applyOnlyWhenPopulated = false
    ): static {
        if (!$condition) {
            return $this;
        }
        $this->addAttribute(new Attribute('data-min-num', $minLength));
        $this->addAttribute(new Attribute('data-min-num-message', $message));
        if($applyOnlyWhenPopulated) {
            $this->addAttribute(new Attribute('data-apply-only-when-populated', $applyOnlyWhenPopulated));
        }
        return $this;
    }

    public function max(
        int $maxLength,
        string $message,
        bool $condition = true,
        bool $applyOnlyWhenPopulated = false
    ): static {
        if (!$condition) {
            return $this;
        }
        $this->addAttribute(new Attribute('data-max-num', $maxLength));
        $this->addAttribute(new Attribute('data-max-num-message', $message));
        if($applyOnlyWhenPopulated) {
            $this->addAttribute(new Attribute('data-apply-only-when-populated', $applyOnlyWhenPopulated));
        }
        return $this;
    }
}