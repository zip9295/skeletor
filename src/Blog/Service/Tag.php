<?php

namespace Skeletor\Blog\Service;

use Skeletor\Blog\Repository\TagRepository;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\TableView\Service\TableView;
use Skeletor\Tenant\Repository\TenantRepositoryInterface as TenantRepository;
use Skeletor\User\Service\Session;

class Tag extends TableView
{
    public function __construct(
        TagRepository $repository,
        Session $session,
        Logger $logger,
        \Skeletor\Blog\Filter\Tag $filter,
        \Skeletor\Core\Activity\Service\Activity $activity,
        ?TenantRepository $tenant = null) {
        parent::__construct($repository, $session, $logger, $filter, $tenant, activity: $activity);
    }

    public function prepareEntities($entities): array
    {
        $items = [];
        foreach($entities as $entity) {
            $itemData = [
                'id' => $entity->id,
                'title' => $entity->title,
                'slug' => $entity->slug,
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
            ['name' => 'title', 'label' => 'Name'],
            ['name' => 'slug', 'label' => 'Slug'],
            ['name' => 'createdAt', 'label' => 'Created at'],
            ['name' => 'updatedAt', 'label' => 'Updated at']
        ];
    }
}