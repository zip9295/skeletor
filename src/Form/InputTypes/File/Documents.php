<?php

namespace Skeletor\Form\InputTypes\File;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\DocumentsInputTypeInterface;


class Documents extends BaseInputType implements DocumentsInputTypeInterface
{
    public function __construct(
        protected string $name,
        protected ?string $label = null,
        protected array $documentData = [],
        protected array $classList = [],
        protected ?string $tooltip = null,
        protected bool $readOnly = false
    )
    {
        parent::__construct($name, $label, $classList, null, $tooltip, $readOnly);
        return $this;
    }

    public function getDocumentData(): array
    {
        return $this->documentData;
    }
}