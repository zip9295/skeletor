<?php

namespace Skeletor\Form\InputTypes\Validation;

use DateTime;
use Skeletor\Form\Attribute\Attribute;

trait DateValidation
{
    public function beforeDate(
        string $date,
        string $message,
        bool $condition = true,
        bool $applyOnlyWhenPopulated = false
    ): static {
        if (!$condition) {
            return $this;
        }
        $dateString = (new DateTime($date))->format('Y-m-d');
        $this->addAttribute(new Attribute('data-before-date', $dateString));
        $this->addAttribute(new Attribute('data-before-date-message', $message));
        if($applyOnlyWhenPopulated) {
            $this->addAttribute(new Attribute('data-apply-only-when-populated', $applyOnlyWhenPopulated));
        }
        return $this;
    }

    public function afterDate(
        string $date,
        string $message,
        bool $condition = true,
        bool $applyOnlyWhenPopulated = false
    ): static {
        if (!$condition) {
            return $this;
        }
        $dateString = (new DateTime($date))->format('Y-m-d');
        $this->addAttribute(new Attribute('data-after-date', $dateString));
        $this->addAttribute(new Attribute('data-after-date-message', $message));
        if($applyOnlyWhenPopulated) {
            $this->addAttribute(new Attribute('data-apply-only-when-populated', $applyOnlyWhenPopulated));
        }
        return $this;
    }

    public function beforeOtherDate(
        string $id,
        $message,
        bool $condition = true,
        bool $applyOnlyWhenPopulated = false
    ): static {
        if (!$condition) {
            return $this;
        }
        $this->addAttribute(new Attribute('data-before-other-date', $id));
        $this->addAttribute(new Attribute('data-before-other-date-message', $message));
        if($applyOnlyWhenPopulated) {
            $this->addAttribute(new Attribute('data-apply-only-when-populated', $applyOnlyWhenPopulated));
        }
        return $this;
    }

    public function afterOtherDate(
        string $id,
        $message,
        bool $condition = true,
        bool $applyOnlyWhenPopulated = false
    ): static {
        if (!$condition) {
            return $this;
        }
        $this->addAttribute(new Attribute('data-after-other-date', $id));
        $this->addAttribute(new Attribute('data-after-other-date-message', $message));
        if($applyOnlyWhenPopulated) {
            $this->addAttribute(new Attribute('data-apply-only-when-populated', $applyOnlyWhenPopulated));
        }
        return $this;
    }


}