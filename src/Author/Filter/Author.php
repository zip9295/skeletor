<?php

namespace Skeletor\Author\Filter;

use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Security\Csrf;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Author\Validator\Author as Validator;

class Author implements FilterInterface
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
        $filteredData = [
            'id' => (isset($data['id'])) ? (int) ($data['id']) : null,
            'firstName' => trim($data['firstName'] ?? ''),
            'lastName' => trim($data['lastName'] ?? ''),
            'displayName' => ($data['displayName'] ?? '') !== '' ? trim($data['displayName']) : null,
            'isActive' => (bool) ($data['isActive'] ?? false),
            'description' => $data['description'] ?? null,
            'shortDescription' => $data['shortDescription'] ?? null,
            'avatarId' => $data['avatarId'] ?? '',
            'seoTitle' => $data['seoTitle'] ?? null,
            'seoDescription' => $data['seoDescription'] ?? null,
            'seoImageId' => $data['seoImageId'] ?? '',
            Csrf::TOKEN_NAME => $data[Csrf::TOKEN_NAME],
        ];

        if (!$this->validator->isValid($filteredData)) {
            throw new ValidatorException();
        }

        unset($filteredData[Csrf::TOKEN_NAME]);

        return $filteredData;
    }
}
