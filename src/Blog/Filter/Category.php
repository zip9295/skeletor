<?php

namespace Skeletor\Blog\Filter;

use Skeletor\Blog\Validator\Category as CategoryValidator;
use Skeletor\Blog\Service\UrlHelper;
use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Core\Security\Csrf;

class Category implements FilterInterface
{
    public function __construct(private CategoryValidator $validator) {}

    public function getErrors(): array
    {
        return $this->validator->getMessages();
    }

    public function filter(array $data): array
    {
        // trim() of the slug, not trim() of the comparison's boolean.
        if (trim((string) ($data['slug'] ?? '')) !== '') {
            $slug = UrlHelper::slugify($data['slug']);
        } else {
            $slug = UrlHelper::slugify($data['title']);
        }

        $filteredData = [
            'id' => (isset($data['id'])) ? (int) ($data['id']) : null,
            'title' => $data['title'],
            'slug' => $slug,
            'parent' => $data['parent'] ?? null,
            'level' => $data['level'] ?? 1,
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