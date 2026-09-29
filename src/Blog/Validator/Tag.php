<?php

namespace Skeletor\Blog\Validator;

use Skeletor\Blog\Repository\TagRepository;
use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Core\Validator\ValidatorInterface;
use Skeletor\Core\Security\Csrf;

class Tag implements ValidatorInterface
{
    private array $messages = [];

    public function __construct(private Csrf $csrf, protected TagRepository $tagRepository)
    {
    }

    public function isValid(array $data): bool
    {
        $valid = true;
        if (!$this->csrf->validate($data)) {
            throw new InvalidFormTokenException();
        }

        if(trim($data['title']) === '') {
            $this->messages['title'][] = 'Title is required.';
            $valid = false;
        }
        if(trim($data['slug']) === '') {
            $this->messages['slug'][] = 'Slug is required.';
            $valid = false;
        }

        if($data['slug']) {
            $tag = $this->tagRepository->fetchAll(['slug' => $data['slug']]);
            if(isset($tag[0])) {
                // Just "are we editing" -- see Blog\Validator\Category for the same repair.
                if(isset($data['id'])) {
                    if($tag[0]->id !== $data['id']) {
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