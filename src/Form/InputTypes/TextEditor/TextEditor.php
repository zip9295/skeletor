<?php

namespace Skeletor\Form\InputTypes\TextEditor;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\TextEditorInputTypeInterface;
use Skeletor\Form\InputTypes\Validation\RequiredValidation;

class TextEditor extends BaseInputType implements TextEditorInputTypeInterface
{
    use RequiredValidation;
    public function __construct(
        protected string $name,
        protected ?string $value = null,
        protected ?string $label = null,
        protected array $classList = [],
        protected ?string $id = null,
        protected ?string $tooltip = null,
        protected bool $readOnly = false
    ) {
        parent::__construct($name, $label, $classList, $id, $tooltip, $readOnly);
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}