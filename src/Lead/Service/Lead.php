<?php

namespace Skeletor\Lead\Service;

use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\TableView\Service\TableView;
use Skeletor\User\Service\Session;

class Lead extends TableView
{
    public function __construct(
        \Skeletor\Lead\Repository\LeadRepository $repo, Session $session, Logger $logger, \Skeletor\Lead\Filter\Lead $filter,
        \Skeletor\Core\Activity\Service\Activity $activity) {
        parent::__construct($repo, $session, $logger, $filter, activity: $activity);
    }

    public function getByEmail($email)
    {
        return $this->repo->findByEmail($email);
    }

    public function prepareEntities($entities)
    {
        $items = [];
        foreach ($entities as $lead) {
            $itemData = [
                'id' => $lead->id,
                'email' => [
                    'value' => $lead->email,
                    'editColumn' => true,
                ],
                'firstName' => $lead->firstName ?? '',
                'lastName' => $lead->lastName ?? '',
                'phoneNumber' => $lead->phoneNumber ?? '',
                'status' => $lead->status ?? '',
                'createdAt' => $lead->createdAt->format('d.m.Y'),
                'updatedAt' => $lead->updatedAt->format('d.m.Y'),
            ];
            $items[] = [
                'columns' => $itemData,
                'id' => $lead->id,
            ];
        }
        return $items;
    }

    public function compileTableColumns()
    {

        return [
            ['name' => 'email', 'label' => 'Email'],
            ['name' => 'firstName', 'label' => 'First name'],
            ['name' => 'lastName', 'label' => 'Last name'],
            ['name' => 'phoneNumber', 'label' => 'Phone'],
            ['name' => 'status', 'label' => 'Status'],
            ['name' => 'updatedAt', 'label' => 'Updated at'],
            ['name' => 'createdAt', 'label' => 'Created at', 'rangeFilter' => ['type' => 'date']],
        ];
    }
}