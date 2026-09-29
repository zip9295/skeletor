<?php

namespace Skeletor\Form\InputTypes\File;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\DocumentInputTypeInterface;
use Skeletor\Form\InputTypes\Validation\RequiredValidation;

class Document extends BaseInputType implements DocumentInputTypeInterface
{
    use RequiredValidation;

    public function __construct(
        protected string $name,
        protected string $chooseDocumentText = 'Choose Document',
        protected ?string $label = null,
        protected ?string $filename = null,
        protected null|int|string $documentId = null,
        protected array $classList = [],
        protected ?string $id = null,
        protected ?string $tooltip = null,
        protected bool $readOnly = false
    )
    {
        parent::__construct($name, $label, $classList, $id, $tooltip, $readOnly);
        return $this;
    }

    public function getDocumentId(): null|int|string
    {
        return $this->documentId;
    }

    public function getChooseDocumentText(): string
    {
        return $this->chooseDocumentText;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }
}