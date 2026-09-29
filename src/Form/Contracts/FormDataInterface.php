<?php

namespace Skeletor\Form\Contracts;

interface FormDataInterface
{
    public function getId(): string;

    public function getAction(): string;

    public function getMethod(): string;

    public function getEnctype(): string;

    public function getSubmitText(): string;

    public function getDataAction(): string;

    public function getCsrfToken(): array;

    public function isReadOnly(): bool;
}