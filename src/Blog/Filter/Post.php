<?php

namespace Skeletor\Blog\Filter;

use Skeletor\Blog\Validator\Post as PostValidator;
use Skeletor\Blog\Service\UrlHelper;
use Skeletor\ContentEditor\Contracts\ContentEditorFilterInterface;
use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Core\Security\Csrf;

class Post implements FilterInterface
{

    public function __construct(
        private PostValidator $validator,
        protected ContentEditorFilterInterface $blockFilter
    ) {}

    public function getErrors(): array
    {
        return $this->validator->getMessages();
    }

    public function filter(array $data): array
    {
        if ($data['slug'] && trim($data['slug'] !== '')) {
            $slug = UrlHelper::slugify($data['slug']);
        } else {
            $slug = UrlHelper::slugify($data['title']);
        }
        $statusData = json_decode($data['status'], true);
        $seoData = json_decode($data['seo'], true);
        $featuredImage = json_decode($data['featuredImage'], true);
        $categoryData = json_decode($data['categories']);
        $authors = json_decode($data['authors'], true);
        $mainCategory = array_shift($categoryData);
        $blockData = $this->blockFilter->filter(json_decode($data['blocks'] ?? [], true) ?? []);

        $filteredData = [
            'id' => (isset($data['id'])) ? (int) ($data['id']) : null,
            'isLiveBlogPost' => isset($data['isLiveBlogPost']) && $data['isLiveBlogPost'] === 'on',
            'title' => $data['title'],
            'slug' => $slug,
            'shortDescription' => $data['excerpt'],
            'blockData' => $blockData,
            'status' => (int)$statusData['status'],
            'featuredImageId' => $featuredImage['id'] ?? null,
            'tags' => json_decode($data['tags'] ?? [], true) ?? [],
            'mainCategory' => $mainCategory,
            'author' => $authors[0] ?? null,
            'categories' => $categoryData ?? [],
            'publishAt' => isset($statusData['schedule']) ? new \DateTime($statusData['schedule']) : null,
            'seoTitle' => $seoData['title'] ?? null,
            'seoDescription' => $seoData['description'] ?? null,
            'seoImageId' => $seoData['image']['id'] ?? null,
            Csrf::TOKEN_NAME => $data[Csrf::TOKEN_NAME],
        ];

        if (!$this->validator->isValid($filteredData)) {
            throw new ValidatorException();
        }

        unset($filteredData[Csrf::TOKEN_NAME]);

        return $filteredData;
    }
}