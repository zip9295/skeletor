<?php

namespace Skeletor\Blog\Service;

use Skeletor\Blog\Filter\Post as PostFilter;
use Skeletor\Blog\Repository\PostRepository;
use Skeletor\Reference\Service\Reference;
use DOMDocument;
use DOMXPath;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Cache\Service\ObjectCache;
use Skeletor\Core\TableView\Service\TableView;
use Skeletor\Tenant\Repository\TenantRepositoryInterface as TenantRepository;
use Skeletor\User\Service\Session;
use Symfony\Contracts\Cache\ItemInterface;

class Post extends TableView
{
    public function __construct(
        PostRepository $repository,
        Session $session,
        Logger $logger,
        PostFilter $filter,
        private Category $category,
        protected Reference $referenceService,
        \Skeletor\Core\Activity\Service\Activity $activity,
        ?TenantRepository $tenant = null) {
        parent::__construct($repository, $session, $logger, $filter, $tenant, activity: $activity);
    }

    public function getPublishedOrdered(): array
    {
        return $this->repo->getPublishedOrdered();
    }

    public function getPosts($categoryId, $limit = 15, $offset = 0)
    {
        return $this->repo->getPosts($categoryId, $limit, $offset);
    }

    public function search($query)
    {
        return $this->repo->search($query);
    }

    public function getByIdCached($postId)
    {
        return $this->repo->getByIdCached($postId);
    }

    public function prepareEntities($entities): array
    {
        $items = [];
        foreach($entities as $entity) {
            $categories = [];
            foreach ($entity->categories as $category) {
                $categories[] = $category->title;
            }

            $itemData = [
                'id' => $entity->id,
                'title' =>  [
                    'value' => $entity->title,
                    'editColumn' => true,
                ],
                'slug' => $entity->slug,
                'mainCategory' => $entity->mainCategory->title,
                'categories' => implode(',', $categories),
                'isLiveBlogPost' => $entity->isLiveBlogPost ? 'Yes' : 'No',
                'status' => \Skeletor\Blog\Entity\Post::getHrStatus($entity->status),
                'author' => $entity->author->displayName,
                'publishAt' => $entity->publishAt?->format('d.m.Y'),
                'createdAt' => $entity->createdAt->format('d.m.Y'),
                'updatedAt' => $entity->updatedAt->format('d.m.Y'),
            ];
            $items[] = [
                'columns' => $itemData,
                'id' => $entity->id,
            ];
        }
        return $items;
    }

    public function compileTableColumns(): array
    {
        $categories = $this->category->getFilterData([], null, null, 'title');

        return [
            ['name' => 'id', 'label' => 'ID'],
            ['name' => 'title', 'label' => 'Name'],
            ['name' => 'slug', 'label' => 'Slug'],
            ['name' => 'mainCategory', 'label' => 'Category', 'filterData' => $categories],
            ['name' => 'categories', 'label' => 'Categories', 'filterData' => $categories],
            ['name' => 'status', 'label' => 'Status', 'filterData' => \Skeletor\Blog\Entity\Post::getHrStatuses()],
            ['name' => 'isLiveBlogPost', 'label' => 'Live', 'filterData' => [1 => 'Yes', 0 => 'No']],
            ['name' => 'publishAt', 'label' => 'Publish at', 'rangeFilter' => ['type' => 'date']],
            ['name' => 'author', 'label' => 'Author'],
            ['name' => 'createdAt', 'label' => 'Created', 'rangeFilter' => ['type' => 'date']],
            ['name' => 'updatedAt', 'label' => 'Updated', 'rangeFilter' => ['type' => 'date']]
        ];
    }

    public function getPostCount(int $status)
    {
        return $this->repo->getEntityCount(['status' => $status]);
    }

    public function getPostsForTag(int $tagId)
    {
        return $this->repo->getPostsForTag($tagId);
    }

    public function getCountPostsForTag(int $tagId)
    {
        return $this->repo->getCountPostsForTag($tagId);
    }

    public function getCountPostsForCategory(int $categoryId)
    {
        return $this->repo->getCountPostsForCategory($categoryId);
    }

    public function getNewestPublishedPostsWithExcludedIds(int $limit, ?int $categoryId = null, array $excludedIds = [])
    {
        return $this->repo->getNewestPublishedPostsWithExcludedIds($limit, $categoryId, $excludedIds);
    }

    public function getPublishedPostsByIds(array $ids)
    {
        return $this->repo->getPublishedPostsByIds($ids);
    }
}