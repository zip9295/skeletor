<?php
namespace Skeletor\Address\Service;

use Psr\Log\LoggerInterface as Logger;
use Skeletor\Address\Repository\AddressRepository;
use Skeletor\Core\TableView\Service\TableView as TableView;

class Address extends TableView
{
    const ENTITY = \Skeletor\Address\Entity\Address::class;
    const MODEL = \Skeletor\Address\Model\Address::class;

    public function __construct(
        AddressRepository $repository, \Skeletor\User\Service\Session $userSession, Logger $logger,
        \Skeletor\Address\Filter\Address $filter,
        \Skeletor\Core\Activity\Service\Activity $activity) {
        parent::__construct($repository, $userSession, $logger, $filter, activity: $activity);
    }

    /**
     * @param $entityId
     * @param $type
     * @return array
     */
    public function getByEntity($entityId, $type)
    {
        return $this->repo->fetchAll(['entityId' => $entityId, 'type' => $type]);
    }

    public function prepareEntities($entities)
    {
        $items = [];
        foreach ($entities as $address) {
            $item = [
                'id' => $address->getId(),
                'address' =>  [
                    'value' => $address->getAddress(),
                    'editColumn' => true,
                ],
                'streetNumber' => $address->getStreetNumber(),
                'createdAt' => $address->getCreatedAt()->format('d.m.Y'),
                'updatedAt' => $address->getUpdatedAt()->format('d.m.Y'),
            ];
            $items[] = [
                'columns' => $item,
                'id' => $address->getId(),
            ];
        }

        return $items;
    }

    public function compileTableColumns()
    {
        $columnDefinitions = [
            ['name' => 'address', 'label' => 'Address'],
            ['name' => 'streetNumber', 'label' => 'streetNumber'],
            ['name' => 'updatedAt', 'label' => 'Updated at'],
            ['name' => 'createdAt', 'label' => 'Created at'],
        ];

        return $columnDefinitions;
    }


}