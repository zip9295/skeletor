<?php

namespace Skeletor\Form\Base;

use Skeletor\Form\Contracts\FormDataInterface;

abstract class BaseForm implements FormDataInterface
{
    public function __construct(
        protected string $action,
        protected string $dataAction,
        protected array $csrfToken,
        protected ?string $id = 'crudForm',
        protected ?string $method = 'POST',
        protected ?string $enctype = 'multipart/form-data',
        protected ?string $submitText = 'Save',
        protected bool $readOnly = false
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEnctype(): string
    {
        return $this->enctype;
    }

    public function getSubmitText(): string
    {
        return $this->submitText;
    }

    public function getDataAction(): string
    {
        return $this->dataAction;
    }

    public function getCsrfToken(): array
    {
        return $this->csrfToken;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }
}