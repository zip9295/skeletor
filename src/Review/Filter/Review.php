<?php

namespace Skeletor\Review\Filter;

use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Review\Validator\Review as Validator;
use Skeletor\Core\Security\Csrf;

class Review implements FilterInterface
{
    public function __construct(private Validator $validator) {}

    public function getErrors(): array
    {
        return $this->validator->getMessages();
    }

    /**
     * @throws ValidatorException
     */
    public function filter(array $data, bool $useCSRF = true): array
    {
        if($data['rating'] > 10) {
            $data['rating'] = 10;
        }
        if($data['rating'] < 1) {
            $data['rating'] = 1;
        }
        $data['body'] = filter_var($data['body'], FILTER_SANITIZE_ADD_SLASHES);
        if (!$this->validator->isValid($data, $useCSRF)) {
            throw new ValidatorException();
        }
        unset($data[Csrf::TOKEN_NAME]);

        return $data;
    }
}