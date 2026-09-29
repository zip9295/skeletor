<?php

namespace Skeletor\Page\Validator;

use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Core\Validator\ValidatorInterface;
use Skeletor\Page\Repository\PageRepository;
use Skeletor\Core\Security\Csrf;

class Page implements ValidatorInterface
{
    private $messages = [];


    public function __construct(private Csrf $csrf, protected PageRepository $pageRepository)
    {
    }

    public function isValid(array $data): bool
    {
        if (!$this->csrf->validate($data)) {
            throw new InvalidFormTokenException();
        }
        $valid = true;
        if(trim((string) ($data['title'] ?? '')) === '') {
            $this->messages['title'][] = 'Title is required.';
            $valid = false;
        }
        // No status check. Page has no "please select" sentinel -- STATUS_NEW is 0 and is a
        // real status -- and the filter casts to int with a 0 default, so there is nothing to
        // reject. There used to be a `$data['status'] === '-1'` test here, copied from a module
        // that does have a sentinel; it compared an int to a string with === and could never
        // fire. Removed rather than repaired, because repairing it would start rejecting
        // legitimately-new pages.

        if($data['slug']) {
            $page = $this->pageRepository->fetchAll(['slug' => $data['slug']]);
            if(isset($page[0])) {
                // Just "are we editing". The filter gives id as int|null, so isset() answers
                // it on its own; this used to read `trim($data['id'] !== '')`, which trims a
                // boolean -- always truthy, and one refactor from silently inverting.
                if(isset($data['id'])) {
                    if($page[0]->id !== $data['id']) {
                        $this->messages['slug'][] = 'Slug already exists.';
                        $valid = false;
                    }
                } else {
                    $this->messages['slug'][] = 'Slug already exists.';
                    $valid = false;
                }
            }
        }

        if(!isset($data['seoTitle']) || trim($data['seoTitle']) === '') {
            $this->messages['seoTitle'][] = 'SEO Title is required.';
            $valid = false;
        }
        if(!isset($data['seoDescription']) || trim($data['seoDescription']) === '') {
            $this->messages['seoDescription'][] = 'SEO Description is required.';
            $valid = false;
        }
        if(!isset($data['seoImageId']) || trim($data['seoImageId']) === '') {
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