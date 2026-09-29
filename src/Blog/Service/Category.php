<?php

namespace Skeletor\Blog\Service;

use Skeletor\Blog\Repository\CategoryRepository;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\TableView\Service\TableView;
use Skeletor\Tenant\Repository\TenantRepositoryInterface as TenantRepository;
use Skeletor\User\Service\Session;

class Category extends TableView
{
    public function __construct(
        CategoryRepository $repository,
        Session $session,
        Logger $logger,
        \Skeletor\Blog\Filter\Category $filter,
        \Skeletor\Core\Activity\Service\Activity $activity,
        ?TenantRepository $tenant = null) {
        parent::__construct($repository, $session, $logger, $filter, $tenant, activity: $activity);
    }

    public function getHierarchy(): array
    {
        $categories = [];
        /* @var \Skeletor\Blog\Entity\Category $category */
        foreach ($this->getEntities(['parent' => null]) as $category) {
            $category->children = $this->getEntities(['parent' => $category->id]);
            $categories[] = $category;
        }
        return $categories;
    }

    public function prepareEntities($entities): array
    {
        $items = [];
        foreach($entities as $entity) {
            $itemData = [
                'id' => $entity->id,
                'title' => $entity->title,
                'slug' => $entity->slug,
                'parent' => $entity?->parent->title ?? '',
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
        return [
            ['name' => 'id', 'label' => 'ID'],
            ['name' => 'title', 'label' => 'Name'],
            ['name' => 'slug', 'label' => 'Slug'],
            ['name' => 'parent', 'label' => 'Parent'],
            ['name' => 'createdAt', 'label' => 'Created at'],
            ['name' => 'updatedAt', 'label' => 'Updated at']
        ];
    }
}