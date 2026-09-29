<?php

namespace Skeletor\Form\InputTypes\Validation;

use Skeletor\Form\Attribute\Attribute;

trait TextValidation
{
    public function minLength(
        int $minLength,
        string $message,
        bool $condition = true,
        bool $applyOnlyWhenPopulated = false
    ): static {
        if (!$condition) {
            return $this;
        }
        $this->addAttribute(new Attribute('data-min-len', $minLength));
        $this->addAttribute(new Attribute('data-min-len-message', $message));
        if($applyOnlyWhenPopulated) {
            $this->addAttribute(new Attribute('data-apply-only-when-populated', $applyOnlyWhenPopulated));
        }
        return $this;
    }

    public function maxLength(
        int $maxLength,
        string $message,
        bool $condition = true,
        bool $applyOnlyWhenPopulated = false
    ): static {
        if (!$condition) {
            return $this;
        }
        $this->addAttribute(new Attribute('data-max-len', $maxLength));
        $this->addAttribute(new Attribute('data-max-len-message', $message));
        if($applyOnlyWhenPopulated) {
            $this->addAttribute(new Attribute('data-apply-only-when-populated', $applyOnlyWhenPopulated));
        }
        return $this;
    }

    public function exactLength(
        int $exactLength,
        string $message,
        bool $condition = true,
        bool $applyOnlyWhenPopulated = false
    ): static {
        if (!$condition) {
            return $this;
        }
        $this->addAttribute(new Attribute('data-exact-len', $exactLength));
        $this->addAttribute(new Attribute('data-exact-len-message', $message));
        if($applyOnlyWhenPopulated) {
            $this->addAttribute(new Attribute('data-apply-only-when-populated', $applyOnlyWhenPopulated));
        }
        return $this;
    }
}