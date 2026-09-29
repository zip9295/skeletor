<?php
namespace Skeletor\Core\Repository;

use Doctrine\ORM\EntityManagerInterface;
use League\Event\EventDispatcher;
use Skeletor\Core\Mapper\MysqlCrudMapper;
use Skeletor\Core\Model\Model as Model;

abstract class CrudRepository implements RepositoryInterface
{
    const ENTITY = null;

    const FACTORY = null;

    public function __construct(
        protected EntityManagerInterface $entityManager
    ) { }

    /**
     * @param string $name
     * @return Model
     * @throws \Exception
     */
    public function getByName(string $name): \Skeletor\Core\Model\Model
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Wrong param provided. ');
        }
        $data = $this->entityManager->getRepository(static::ENTITY)->findBy(['name' => $name]);
        if (empty($data)) {
            throw new \Exception('Entity not found. ' . $name);
        }

        return $items[] = static::FACTORY::formatForRead($data[0]);
    }

    /**
     * @param $name
     * @return bool
     */
    public function nameExists(string $name): bool
    {
        try {
            $this->getByName($name);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function fetchAll($params = array(), $limit = null, $order = null, $returnArray = null, $offset = null): array
    {
        $items = [];
        foreach ($this->entityManager->getRepository(static::ENTITY)->findBy($params, $order, $limit, $offset) as $entity) {
            if (!$returnArray) {
                $items[] = static::FACTORY::formatForRead($entity);
//                $items[] = $entity;
            } else {
                $id = $entity->id;
                $items = $this->entityManager->getUnitOfWork()->getOriginalEntityData($entity); // @TODO check this
                $item['id'] = $id;
                $items[] = $item;
            }
        }

        return $items;
    }

    public function getById($id) //: Skeletor\User\Entity\User
    {
        return static::FACTORY::formatForRead($this->entityManager->getRepository(static::ENTITY)->find($id));

//        $data = $this->entityManager->getUnitOfWork()->getOriginalEntityData($entity);
//        // @TODO id is missing somehow
//        if (!isset($data['id']) || !$data['id']) {
//            $data['id'] = $id;
//        }
//
//
//        return static::FACTORY::make(
//            $entity, $data, $this->entityManager
//        );
    }

    public function create($data)
    {
        $id = static::FACTORY::compileEntityForCreate($data, $this->entityManager);

        return $this->getById($id);
    }

    public function update($data)
    {
        $id = static::FACTORY::compileEntityForUpdate($data, $this->entityManager);
        $this->entityManager->flush();

        return $this->getById($id);
    }

    public function delete($id): bool
    {
        $entity = $this->entityManager->getRepository(static::ENTITY)->find($id);
        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        return true;
    }

//    protected function convertEntityToModel($entity)
//    {
//        return static::FACTORY::make(
//            $entity, $this->entityManager->getUnitOfWork()->getOriginalEntityData($entity), $this->entityManager
//        );
//    }

    public function updateField($field, $value, $entityId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        if (!is_numeric($value)) {
            $value = "'".$value."'";
        }
        $qb->update(static::ENTITY, 'a')->set('a.' . $field, $value)->where($qb->expr()->eq('a.id', $entityId));
        $qb->getQuery()->execute();
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function getPrimaryKeyIdentifier()
    {
        return 'id';
    }
    public function beginTransaction()
    {
        $this->entityManager->beginTransaction();
    }

    public function getEntityCount($filter = []): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('count(a.id)')
            ->from(static::ENTITY, 'a');
        foreach ($filter as $key => $value) {
            $qb->andWhere(sprintf('a.%s = :%s', $key, $key));
            $qb->setParameter($key, $value);
        }
        return $qb->getQuery()->getSingleScalarResult();
    }

//    public function commitTransaction(): bool
//    {
//        return $this->mapper->commitTransaction();
//    }
//
//    public function rollbackTransaction(): bool
//    {
//        return $this->mapper->rollBackTransaction();
//    }
}