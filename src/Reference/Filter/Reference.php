<?php

namespace Skeletor\Reference\Filter;

use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Core\Security\Csrf;

class Reference implements FilterInterface
{
    public function __construct(private \Skeletor\Reference\Validator\Reference $validator) {}

    public function getErrors(): array
    {
        return $this->validator->getMessages();
    }

    public function filter(array $data): array
    {
        $filteredData = [
            'id' => (isset($data['id'])) ? (int) ($data['id']) : null,
            'title' => $data['title'],
            'comment' => $data['comment'],
            'content' => $data['content'],
            'status' => (int) ($data['status']),
            Csrf::TOKEN_NAME => $data[Csrf::TOKEN_NAME],
        ];

        if (!$this->validator->isValid($filteredData)) {
            throw new ValidatorException();
        }

        unset($filteredData[Csrf::TOKEN_NAME]);

        return $filteredData;
    }
}