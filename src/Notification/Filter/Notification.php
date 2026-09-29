<?php

namespace Skeletor\Notification\Filter;

use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Core\Security\Csrf;

class Notification implements FilterInterface
{
    public function __construct(private readonly \Skeletor\Notification\Validator\Notification $validator) {}

    public function getErrors(): array
    {
        return $this->validator->getMessages();
    }

    /**
     * @throws ValidatorException
     */
    public function filter(array $data, bool $useCSRF = true): array
    {
        if (!$this->validator->isValid($data, $useCSRF)) {
            throw new ValidatorException();
        }
        unset($data[Csrf::TOKEN_NAME]);

        return $data;
    }
}