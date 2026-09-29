<?php

namespace Skeletor\Core\Validator;

class DuplicatedValueException extends \Exception
{
    public function __construct(public string|null $fieldName = null, public mixed $fieldValue = null) {
        parent::__construct();
    }
}