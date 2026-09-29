<?php
namespace Skeletor\Core\Service;

use Skeletor\Core\Repository\ReadRepositoryInterface as Repository;
abstract class ReadService
{
    protected $repo;

    /**
     * @param Repository $repo
     */
    public function __construct(Repository $repo)
    {
        $this->repo = $repo;
    }

    public function getEntities($filter = [], $limit = null, $order = null)
    {
        $data = [];
        foreach ($this->repo->fetchAll($filter, $limit, $order) as $entity) {
            $data[] = $entity;
        }

        return $data;
    }

    public function getEntityData(int $id)
    {
        return $this->repo->getById($id)->toArray();
    }

    public function getById(int $id)
    {
        return $this->repo->getById($id);
    }

}