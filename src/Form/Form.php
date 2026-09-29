<?php

namespace Skeletor\Form;

use Skeletor\Form\Base\BaseForm;
use Skeletor\Form\Contracts\FormInterface;
use Skeletor\Form\InputGroup\Collection\InputGroupCollection;
use Skeletor\Form\InputGroup\Contracts\InputGroupCollectionInterface;
use Skeletor\Form\InputGroup\Contracts\InputGroupInterface;

final class Form extends BaseForm implements FormInterface
{
    public function __construct(
        protected string $action,
        protected string $dataAction,
        protected array $csrfToken,
        protected ?string $id = 'crudForm',
        protected ?string $method = 'POST',
        protected ?string $enctype = 'multipart/form-data',
        protected ?string $submitText = 'Save',
        protected bool $readOnly = false,
        protected InputGroupCollectionInterface $inputGroupCollection = new InputGroupCollection()
    )
    {
        parent::__construct($action, $dataAction, $csrfToken, $id, $method, $enctype, $submitText, $readOnly);
    }

    public function addInputGroup(InputGroupInterface $inputGroup): InputGroupInterface
    {
        $this->inputGroupCollection->add($inputGroup);
        return $inputGroup;
    }

    public function getInputGroupCollection(): InputGroupCollectionInterface
    {
        return $this->inputGroupCollection;
    }
}