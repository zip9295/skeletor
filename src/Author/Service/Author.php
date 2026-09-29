<?php

namespace Skeletor\Author\Service;

use Psr\Log\LoggerInterface as Logger;
use Skeletor\Author\Entity\Author as Entity;
use Skeletor\Author\Filter\Author as Filter;
use Skeletor\Author\Repository\AuthorRepository as Repository;
use Skeletor\Core\TableView\Service\TableView;
use Skeletor\User\Service\Session;

class Author extends TableView
{
    public function __construct(
        Repository $repository,
        Session $session,
        Logger $logger,
        Filter $filter,
        \Skeletor\Core\Activity\Service\Activity $activity) {
        parent::__construct($repository, $session, $logger, $filter, activity: $activity);
    }

    public function prepareEntities($entities): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $itemData = [
                'id' => $entity->id,
                'firstName' => [
                    'value' => $entity->firstName,
                    'editColumn' => true,
                ],
                'lastName' => $entity->lastName,
                'displayName' => $entity->getDisplayName(),
                'isActive' => $entity->isActive ? 'Yes' : 'No',
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
            ['name' => 'firstName', 'label' => 'First name'],
            ['name' => 'lastName', 'label' => 'Last name'],
            ['name' => 'displayName', 'label' => 'Display name'],
            ['name' => 'isActive', 'label' => 'Active', 'filterData' => ['No', 'Yes']],
            ['name' => 'createdAt', 'label' => 'Created at', 'rangeFilter' => ['type' => 'date']],
            ['name' => 'updatedAt', 'label' => 'Updated at', 'rangeFilter' => ['type' => 'date']],
        ];
    }

    /**
     * @return Entity[]
     */
    public function getActive(): array
    {
        return $this->repo->getActive();
    }
}
