<?php

namespace Skeletor\ContentEditor\Exceptions;

class TemplateNotFoundException extends \Exception
{
    public function __construct(string $message = 'Template not found')
    {
        parent::__construct($message);
    }
}