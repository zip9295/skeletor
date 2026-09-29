<?php

namespace Skeletor\Attribute\Filter;

use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Attribute\Validator\Attribute as Validator;
use Skeletor\Core\Security\Csrf;

class Attribute implements FilterInterface
{
    public function __construct(private Validator $validator) {}

    public function getErrors(): array
    {
        return $this->validator->getMessages();
    }

    /**
     * @throws ValidatorException
     */
    public function filter(array $data): array
    {
        if (!$this->validator->isValid($data)) {
            throw new ValidatorException();
        }
        unset($data[Csrf::TOKEN_NAME]);
        return $data;
    }
}