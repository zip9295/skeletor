<?php

namespace Skeletor\Form\InputTypes\ContentEditor;

use Skeletor\Form\InputTypes\Base\BaseInputType;
use Skeletor\Form\InputTypes\Contracts\ContentEditorInputTypeInterface;

class ContentEditor extends BaseInputType implements ContentEditorInputTypeInterface
{
    public function __construct(
        protected string $jsonContent = '',
        protected string $searchBlocksPlaceholder = 'Search Blocks...',
        protected array $classList = [],
        protected ?string $id = null,
        protected bool $readOnly = false
    ) {
        parent::__construct('contentEditor', null, $classList, $id, null, $readOnly);
    }

    public function getJsonContent(): string
    {
        return $this->jsonContent;
    }

    public function getSearchBlocksPlaceholder(): string
    {
        return $this->searchBlocksPlaceholder;
    }
}