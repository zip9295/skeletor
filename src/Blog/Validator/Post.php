<?php

namespace Skeletor\Blog\Validator;

use Skeletor\Blog\Repository\PostRepository;
use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Core\Validator\ValidatorInterface;
use Skeletor\Core\Security\Csrf;

class Post implements ValidatorInterface
{
    private array $messages = [];

    public function __construct(private Csrf $csrf, protected PostRepository $postRepository)
    {
    }

    public function isValid(array $data): bool
    {
        $valid = true;
        if (!$this->csrf->validate($data)) {
            throw new InvalidFormTokenException();
        }

        $title = trim((string) ($data['title'] ?? ''));
        if($title === '') {
            $this->messages['title'][] = 'Title is required.';
            $valid = false;
        }
        // Was `trim(strlen($data['title']) < 5)` -- trimming a boolean. It gave the right
        // answer by accident, because trim(true) is "1" and trim(false) is "", but it measured
        // the untrimmed title while the check above trims. A title of five spaces was both
        // required-and-missing and long enough.
        if($title !== '' && strlen($title) < 5) {
            $this->messages['title'][] = 'Title must be at least 5 characters.';
            $valid = false;
        }
        if(trim((string) ($data['slug'] ?? '')) === '') {
            $this->messages['slug'][] = 'Slug is required.';
            $valid = false;
        }
        // Checked against the real statuses rather than against a '-1' sentinel. The filter
        // casts with (int), so an unselected status arrives as 0 -- which is not a Post status
        // and would later blow up in getHrStatus(). The old `=== '-1'` compared an int to a
        // string and could never fire, so nothing caught it.
        if(!array_key_exists($data['status'] ?? null, \Skeletor\Blog\Entity\Post::getHrStatuses())) {
            $this->messages['status'][] = 'Status is required.';
            $valid = false;
        }
        if($data['slug']) {
            $post = $this->postRepository->fetchAll(['slug' => $data['slug']]);
            if(isset($post[0])) {
                // Just "are we editing". The filter gives id as int|null, so isset() answers
                // it; this read `trim($data['id'] !== '')`, which trims a boolean.
                if(isset($data['id'])) {
                    if($post[0]->id !== $data['id']) {
                        $this->messages['slug'][] = 'Slug already exists.';
                        $valid = false;
                    }
                } else {
                    $this->messages['slug'][] = 'Slug already exists.';
                    $valid = false;
                }
            }
        }
        if(!isset($data['mainCategory'])) {
            $this->messages['mainCategory'][] = 'Main Category is required.';
            $valid = false;
        }

        if(!isset($data['seoTitle']) || trim($data['seoTitle']) === '') {
            $this->messages['seoTitle'][] = 'SEO Title is required.';
            $valid = false;
        }
        if(!isset($data['seoDescription']) || trim($data['seoDescription']) === '') {
            $this->messages['seoDescription'][] = 'SEO Description is required.';
            $valid = false;
        }
        if(!isset($data['featuredImageId']) || trim($data['featuredImageId']) === '') {
            $this->messages['featuredImageId'][] = 'Feature Image is required.';
            // Was `return false`, alone among the branches here. It short-circuited the rest,
            // so a post missing its featured image never got told about a missing SEO image or
            // author -- fix one, resubmit, discover the next.
            $valid = false;
        }
        if(!isset($data['seoImageId']) || trim($data['seoImageId']) === '') {
            $this->messages['seoImageId'][] = 'SEO Image is required.';
            $valid = false;
        }
        if(empty($data['author']) || (is_array($data['author']) && count($data['author']) === 0)) {
            $this->messages['author'][] = 'Author required.';
            $valid = false;
        }

        return $valid;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}