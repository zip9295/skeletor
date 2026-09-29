<?php

namespace Skeletor\Form\InputTypes\Validation;

use DateTime;
use Skeletor\Form\Attribute\Attribute;

trait DateTimeValidation
{
    public function beforeDateTime(
        string $date,
        string $message,
        bool $condition = true,
        bool $applyOnlyWhenPopulated = false
    ): static {
        if (!$condition) {
            return $this;
        }
        $dateTimeString = (new DateTime($date))->format('Y-m-d\TH:i');
        $this->addAttribute(new Attribute('data-before-date-time', $dateTimeString));
        $this->addAttribute(new Attribute('data-before-date-time-message', $message));
        if($applyOnlyWhenPopulated) {
            $this->addAttribute(new Attribute('data-apply-only-when-populated', $applyOnlyWhenPopulated));
        }
        return $this;
    }

    public function afterDateTime(
        string $date,
        string $message,
        bool $condition = true,
        bool $applyOnlyWhenPopulated = false
    ): static {
        if (!$condition) {
            return $this;
        }
        $dateTimeString = (new DateTime($date))->format('Y-m-d\TH:i');
        $this->addAttribute(new Attribute('data-after-date-time', $dateTimeString));
        $this->addAttribute(new Attribute('data-after-date-time-message', $message));
        if($applyOnlyWhenPopulated) {
            $this->addAttribute(new Attribute('data-apply-only-when-populated', $applyOnlyWhenPopulated));
        }
        return $this;
    }

    public function beforeOtherDateTime(
        string $id,
        string $message,
        bool $condition = true,
        bool $applyOnlyWhenPopulated = false
    ): static {
        if (!$condition) {
            return $this;
        }
        $this->addAttribute(new Attribute('data-before-other-date-time', $id));
        $this->addAttribute(new Attribute('data-before-other-date-time-message', $message));
        if($applyOnlyWhenPopulated) {
            $this->addAttribute(new Attribute('data-apply-only-when-populated', $applyOnlyWhenPopulated));
        }
        return $this;
    }

    public function afterOtherDateTime(
        string $id,
        $message,
        bool $condition = true,
        bool $applyOnlyWhenPopulated = false
    ): static {
        if (!$condition) {
            return $this;
        }
        $this->addAttribute(new Attribute('data-after-other-date-time', $id));
        $this->addAttribute(new Attribute('data-after-other-date-time-message', $message));
        if($applyOnlyWhenPopulated) {
            $this->addAttribute(new Attribute('data-apply-only-when-populated', $applyOnlyWhenPopulated));
        }
        return $this;
    }
}