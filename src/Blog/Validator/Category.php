<?php

namespace Skeletor\Blog\Validator;

use Skeletor\Blog\Repository\CategoryRepository;
use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Core\Validator\ValidatorInterface;
use Skeletor\Core\Security\Csrf;

class Category implements ValidatorInterface
{
    private array $messages = [];

    public function __construct(private Csrf $csrf, protected CategoryRepository $categoryRepository)
    {
    }

    public function isValid(array $data): bool
    {
        $valid = true;
        if (!$this->csrf->validate($data)) {
            throw new InvalidFormTokenException();
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            $this->messages['title'][] = 'Title is required.';
            $valid = false;
        }
        // Was `trim(strlen($data['title']) < 5)` -- trimming a boolean. It gave the right
        // answer by accident, because trim(true) is "1" and trim(false) is "", but it measured
        // the untrimmed title while the check above trims. '  ab  ' cleared a five character
        // minimum while the stored title was two characters long. Same repair as
        // Blog\Validator\Post, including the guard that keeps an empty title to one message.
        if ($title !== '' && strlen($title) < 5) {
            $this->messages['title'][] = 'Title must be at least 5 characters.';
            $valid = false;
        }
        if (trim((string) ($data['slug'] ?? '')) === '') {
            $this->messages['slug'][] = 'Slug is required.';
            $valid = false;
        }
        if ($data['slug']) {
            $category = $this->categoryRepository->fetchAll(['slug' => $data['slug']]);
            if (isset($category[0])) {
                // Just "are we editing". The filter gives id as int|null, so isset() answers
                // it; this read `trim($data['id'] !== '')`, which trims a boolean.
                if (isset($data['id'])) {
                    if ($category[0]->id !== $data['id']) {
                        $this->messages['slug'][] = 'Slug already exists.';
                        $valid = false;
                    }
                } else {
                    $this->messages['slug'][] = 'Slug already exists.';
                    $valid = false;
                }
            }
        }

        if(!isset($data['seoTitle'])) {
            $this->messages['seoTitle'][] = 'SEO Title is required.';
            $valid = false;
        }
        if(!isset($data['seoDescription'])) {
            $this->messages['seoDescription'][] = 'SEO Description is required.';
            $valid = false;
        }
        if(!isset($data['seoImageId'])) {
            $this->messages['seoImageId'][] = 'SEO Image is required.';
            $valid = false;
        }

        return $valid;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}