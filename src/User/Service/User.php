<?php
namespace Skeletor\User\Service;

use Psr\Log\LoggerInterface as Logger;
use Skeletor\Address\Model\LocationInterface;
use Skeletor\Core\TableView\Service\TableView as TableView;
use Skeletor\Entity\Entity;
use Skeletor\Tenant\Model\Tenant;
use Skeletor\Tenant\Repository\TenantRepositoryInterface;
use Skeletor\User\Repository\UserRepositoryInterface as UserRepo;

class User extends TableView
{
    const TRACK_ACTIVITY = true;
    const ENTITY_TYPE = 'user';

    public function __construct(
        UserRepo $repository, \Skeletor\User\Service\Session $userSession,
        Logger $logger, \Skeletor\User\Filter\User $filter,
        \Skeletor\Core\Activity\Service\Activity $activity,
        protected ?TenantRepositoryInterface $tenant = null) {
        parent::__construct($repository, $userSession, $logger, $filter, $tenant, $activity);
    }

    /**
     * @param array $params
     * @return array
     */
    public function getUsers($params = [])
    {
        return $this->repo->fetchAll($params);
    }

    /**
     * @param $email
     * @return \Skeletor\User\Model\User
     * @throws \Exception
     */
    public function findByEmail($email)
    {
        return $this->repo->findByEmail($email);
    }

    /**
     * @param array $data
     * @return mixed|LocationInterface|void
     */
    public function prepareEntities($entities)
    {
        $items = [];
        foreach ($entities as $user) {
            $itemData = [
                'id' => $user->getId(),
                'email' =>  [
                    'value' => $user->email,
                    'editColumn' => true,
                ],
                'isActive' => ($user->isActive) ? 'Yes':'No',
                'role' => $user::getHrRole($user->role),
                'displayName' => $user->displayName,
                'createdAt' => $user->createdAt->format('d.m.Y'),
                'updatedAt' => $user->updatedAt->format('d.m.Y'),
            ];
            $items[] = [
                'columns' => $itemData,
                'id' => $user->getId(),
            ];
        }
        return $items;
    }

    public function compileTableColumns()
    {
        $columnDefinitions = [
            ['name' => 'email', 'label' => 'Email'],
            ['name' => 'isActive', 'label' => 'Is active', 'filterData' => [1 => 'Active', '0' => 'Inactive']],
            ['name' => 'role', 'label' => 'Role'],
            ['name' => 'displayName', 'label' => 'Display Name'],
            ['name' => 'updatedAt', 'label' => 'Updated at', 'rangeFilter' => ['type' => 'date']],
            ['name' => 'createdAt', 'label' => 'Created at', 'rangeFilter' => ['type' => 'date']],
        ];

        return $columnDefinitions;
    }

    public function getByTenant(Tenant $tenant)
    {
        return $this->repo->getByTenant($tenant);
    }

    public function getEntityData($id)
    {
        $entity = $this->repo->getById($id);

        return [
            'id' => $entity->id,
            'email' => $entity->email,
            'isActive' => $entity->isActive,
            'role' => $entity->role,
            'displayName' => $entity->displayName,
        ];
    }
}