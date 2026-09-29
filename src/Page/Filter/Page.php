<?php

namespace Skeletor\Page\Filter;

use Skeletor\Blog\Service\UrlHelper;
use Skeletor\ContentEditor\Contracts\ContentEditorFilterInterface;
use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Core\Security\Csrf;

class Page implements FilterInterface
{
    protected \Skeletor\Page\Validator\Page $validator;

    public function __construct(\Skeletor\Page\Validator\Page  $validator, protected ContentEditorFilterInterface $blockFilter)
    {
        $this->validator = $validator;
    }

    public function getErrors()
    {
        return $this->validator->getMessages();
    }

    public function filter(array $postData) : array
    {
        $blockData = $this->decode($postData['blocks'] ?? null);
        $statusData = $this->decode($postData['status'] ?? null);
        $featuredImage = $this->decode($postData['featuredImage'] ?? null);
        $seoData = $this->decode($postData['seo'] ?? null);

        $slug = UrlHelper::slugify($postData['title']);
        if (!empty($postData['slug'])) {
            $slug = UrlHelper::slugify($postData['slug']);
        }

        $data = [
            'id' => (isset($postData['id'])) ? (int) ($postData['id']) : null,
            'title' => $postData['title'],
            'slug' => $slug,
            'status' => (int) ($statusData['status'] ?? 0),
            'featuredImageId' => $featuredImage['id'] ?? '',
            'blockData' => $this->blockFilter->filter($blockData),
            'seoTitle' => $seoData['title'] ?? null,
            'seoDescription' => $seoData['description'] ?? null,
            'seoImageId' => $seoData['image']['id'] ?? '',
            Csrf::TOKEN_NAME => $postData[Csrf::TOKEN_NAME],
        ];
        if (!$this->validator->isValid($data)) {
            throw new ValidatorException();
        }
        unset($data[Csrf::TOKEN_NAME]);

        return $data;
    }

    /**
     * A JSON field from the editor's FormData. Already-decoded arrays pass through, so this
     * stays correct if a caller ever posts the payload as a nested array instead.
     */
    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || $value === '') {
            return [];
        }

        return json_decode($value, true) ?? [];
    }
}
