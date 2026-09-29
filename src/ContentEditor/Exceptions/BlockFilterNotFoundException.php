<?php

namespace Skeletor\ContentEditor\Exceptions;

class BlockFilterNotFoundException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}