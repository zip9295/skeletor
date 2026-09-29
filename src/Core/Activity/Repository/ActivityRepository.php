<?php

namespace Skeletor\Core\Activity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Skeletor\Core\Activity\Entity\Activity;
use Skeletor\Core\Activity\Factory\ActivityFactory;
use Skeletor\Core\TableView\Repository\TableViewRepository;

class ActivityRepository extends TableViewRepository
{
    const ENTITY = Activity::class;
    const FACTORY = ActivityFactory::class;

    public function getSearchableColumns(): array
    {
        return ['entityType', 'action'];
    }

    /**
     * Record an activity entry.
     */
    public function record(array $data): Activity
    {
        $entity = new Activity();
        $entity->action = $data['action'];
        $entity->entityType = $data['entityType'];
        $entity->entityId = $data['entityId'];
        $entity->oldData = $data['oldData'] ?? null;
        $entity->newData = $data['newData'] ?? null;
        $entity->diff = $data['diff'] ?? null;
        $entity->ipAddress = $data['ipAddress'] ?? null;

        if (!empty($data['user'])) {
            $entity->user = $data['user'];
        }

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    /**
     * Get full change history for a specific entity, newest first.
     */
    public function getHistory(string $entityType, int $entityId, ?int $limit = null): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')
           ->from(Activity::class, 'a')
           ->where('a.entityType = :type')
           ->andWhere('a.entityId = :id')
           ->setParameter('type', $entityType)
           ->setParameter('id', $entityId)
           ->orderBy('a.id', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the latest activity record for an entity.
     */
    public function getLastActivity(string $entityType, int $entityId): ?Activity
    {
        $result = $this->getHistory($entityType, $entityId, 1);
        return $result[0] ?? null;
    }

    /**
     * Get activity at a specific ID.
     */
    public function getActivityById(int $id): ?Activity
    {
        return $this->entityManager->find(Activity::class, $id);
    }
}
