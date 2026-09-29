<?php

namespace Skeletor\Reference\Service;

use Skeletor\Reference\Repository\ReferenceRepository;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\TableView\Service\TableView;
use Skeletor\User\Service\Session;

class Reference extends TableView
{
    public function __construct(
        ReferenceRepository $repository,
        Session $session,
        Logger $logger,
        \Skeletor\Reference\Filter\Reference $filter,
        \Skeletor\Core\Activity\Service\Activity $activity) {
        parent::__construct($repository, $session, $logger, $filter, activity: $activity);
    }

    public function prepareEntities($entities): array
    {
        $items = [];
        foreach($entities as $entity) {
            $itemData = [
                'id' => $entity->id,
                'title' =>  [
                    'value' => $entity->title,
                    'editColumn' => true,
                ],
                'comment' => $entity->comment,
                'status' => \Skeletor\Reference\Entity\Reference::getHrStatus($entity->status),
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
            ['name' => 'title', 'label' => 'Title'],
            ['name' => 'comment', 'label' => 'Comment'],
            ['name' => 'status', 'label' => 'Status', 'filterData' => \Skeletor\Reference\Entity\Reference::getHrStatuses()],
            ['name' => 'createdAt', 'label' => 'Created at', 'rangeFilter' => ['type' => 'date']],
            ['name' => 'updatedAt', 'label' => 'Updated at', 'rangeFilter' => ['type' => 'date']]
        ];
    }

    public function getByIds(array $ids): array
    {
        return $this->repo->getByIds($ids);
    }
}