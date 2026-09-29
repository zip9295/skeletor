<?php

namespace Skeletor\Core\Activity\Service;

use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Activity\Repository\ActivityRepository;
use Skeletor\Core\TableView\Service\TableView;
use Skeletor\User\Entity\User;
use Skeletor\User\Service\Session;

class Activity extends TableView
{
    public function __construct(
        ActivityRepository $repo,
        Session $userSession,
        Logger $logger,
    ) {
        parent::__construct($repo, $userSession, $logger);
    }

    /**
     * Record an activity entry.
     *
     * @param string $action    'create', 'update', 'restore', or 'delete'
     * @param string $entityType e.g. 'post', 'user', 'category'
     * @param int    $entityId
     * @param array|null $newData   Entity state after change (null for delete)
     * @param array|null $oldData   Entity state before change (null for create)
     * @param User|null  $user      The user who performed the action
     */
    public function record(
        string $action,
        string $entityType,
        int $entityId,
        ?array $newData = null,
        ?array $oldData = null,
        ?User $user = null,
    ): \Skeletor\Core\Activity\Entity\Activity {
        $diff = null;
        if (in_array($action, ['update', 'restore']) && $oldData !== null && $newData !== null) {
            $diff = json_encode($this->computeDiff($oldData, $newData));
        }

        return $this->repo->record([
            'action' => $action,
            'entityType' => $entityType,
            'entityId' => $entityId,
            'oldData' => $oldData !== null ? json_encode($oldData) : null,
            'newData' => $newData !== null ? json_encode($newData) : null,
            'diff' => $diff,
            'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user' => $user,
        ]);
    }

    /**
     * Compute the diff between old and new data.
     * Returns only fields that changed, with old and new values.
     */
    public function computeDiff(array $oldData, array $newData): array
    {
        $diff = [];
        $allKeys = array_unique(array_merge(array_keys($oldData), array_keys($newData)));

        foreach ($allKeys as $key) {
            $oldVal = $oldData[$key] ?? null;
            $newVal = $newData[$key] ?? null;

            if (self::canonical($oldVal) !== self::canonical($newVal)) {
                $diff[$key] = ['old' => $oldVal, 'new' => $newVal];
            }
        }

        return $diff;
    }

    private static function canonical(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $value = array_map([self::class, 'canonical'], $value);
        ksort($value);

        return $value;
    }

    /**
     * Get full change history for an entity.
     */
    public function getEntityHistory(string $entityType, int $entityId): array
    {
        return $this->repo->getHistory($entityType, $entityId);
    }

    /**
     * Reconstruct entity state at a given activity record.
     * Walks backward from the latest state through diffs.
     */
    public function reconstructAt(string $entityType, int $entityId, int $activityId): ?array
    {
        $activity = $this->repo->getActivityById($activityId);
        if (!$activity || $activity->entityType !== $entityType || $activity->entityId !== $entityId) {
            return null;
        }

        // Use the snapshot directly if available
        if ($activity->newData) {
            return json_decode($activity->newData, true);
        }
        if ($activity->oldData) {
            return json_decode($activity->oldData, true);
        }

        return null;
    }

    /**
     * Restore an entity to the state captured in an activity record.
     * Resolves the entity class from Doctrine metadata by matching entityType to class short name.
     */
    public function restoreFromActivity(int $activityId): array
    {
        $activity = $this->repo->getActivityById($activityId);
        if (!$activity) {
            throw new \RuntimeException('Activity record not found.');
        }

        // For updates: restore to the OLD state (before the change happened)
        // For deletes: restore the entity from OLD state (re-create it)
        // For creates: oldData is null — restoring a create means removing, which we don't support
        if ($activity->action === 'create') {
            throw new \RuntimeException('Cannot restore from a create activity. Use delete instead.');
        }

        $restoreData = $activity->oldData ? json_decode($activity->oldData, true) : null;

        if (!$restoreData) {
            throw new \RuntimeException('No snapshot data available for this activity record.');
        }

        $em = $this->repo->getEntityManager();
        $entityClass = $this->resolveEntityClass($em, $activity->entityType);

        if (!$entityClass) {
            throw new \RuntimeException(sprintf('No Doctrine entity found matching type "%s".', $activity->entityType));
        }

        $metadata = $em->getClassMetadata($entityClass);

        // Capture current state before restore (for the restore activity record)
        $currentEntity = $em->find($entityClass, $activity->entityId);
        $beforeRestore = $currentEntity ? self::entityToArray($currentEntity) : null;

        if ($activity->action === 'delete') {
            // Re-create the deleted entity
            $entity = new $entityClass();
            $this->applySnapshotToEntity($entity, $restoreData, $metadata, $em);
            $em->persist($entity);
        } else {
            // Restore entity to its old state
            if (!$currentEntity) {
                throw new \RuntimeException(sprintf('Entity %s #%d not found.', $activity->entityType, $activity->entityId));
            }
            $this->applySnapshotToEntity($currentEntity, $restoreData, $metadata, $em);
            $entity = $currentEntity;
        }

        $em->flush();

        // Record the restore as a new activity entry
        $afterRestore = self::entityToArray($entity);
        $user = $this->getLoggedInUser();
        $this->record('restore', $activity->entityType, $entity->getId(), $afterRestore, $beforeRestore, $user);

        return [
            'entityType' => $activity->entityType,
            'entityId' => $entity->getId(),
            'action' => $activity->action,
        ];
    }

    /**
     * Apply snapshot data to a Doctrine entity, handling relations properly.
     * Scalar properties are set directly. ManyToOne relations are resolved via getReference().
     */
    private function applySnapshotToEntity(
        object $entity,
        array $data,
        \Doctrine\ORM\Mapping\ClassMetadata $metadata,
        \Doctrine\ORM\EntityManagerInterface $em,
    ): void {
        foreach ($data as $key => $value) {
            if (!property_exists($entity, $key) || in_array($key, ['id', 'createdAt', 'updatedAt'])) {
                continue;
            }

            // Check if this field is a ManyToOne association
            if ($metadata->hasAssociation($key)) {
                $mapping = $metadata->getAssociationMapping($key);
                if ($value !== null) {
                    // Use getReference to avoid an extra DB query
                    $entity->$key = $em->getReference($mapping['targetEntity'], $value);
                } else {
                    $entity->$key = null;
                }
            } else {
                $entity->$key = $value;
            }
        }
    }

    /**
     * Get the currently logged-in User entity.
     */
    private function getLoggedInUser(): ?\Skeletor\User\Entity\User
    {
        $userId = $this->loginService->getLoggedInUserId();
        if (!$userId) {
            return null;
        }
        try {
            return $this->repo->getEntityManager()->find(\Skeletor\User\Entity\User::class, $userId);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Resolve a Doctrine entity class from an entityType string.
     * Matches by comparing lowercase short class name to entityType.
     * e.g. 'user' matches Skeletor\User\Entity\User
     */
    private function resolveEntityClass(\Doctrine\ORM\EntityManagerInterface $em, string $entityType): ?string
    {
        $allMetadata = $em->getMetadataFactory()->getAllMetadata();
        foreach ($allMetadata as $metadata) {
            $shortName = strtolower((new \ReflectionClass($metadata->getName()))->getShortName());
            if ($shortName === strtolower($entityType)) {
                return $metadata->getName();
            }
        }
        return null;
    }

    /**
     * Convert an entity to an associative array for storage.
     * Uses get_object_vars for Doctrine entities with public properties.
     */
    public static function entityToArray(object $entity): array
    {
        $data = [];
        foreach (get_object_vars($entity) as $key => $value) {
            if ($value instanceof \DateTime) {
                $data[$key] = $value->format('Y-m-d H:i:s');
            } elseif (is_object($value) && method_exists($value, 'getId')) {
                // Store related entity as its ID
                $data[$key] = $value->getId();
            } elseif (is_scalar($value) || $value === null) {
                $data[$key] = $value;
            } elseif(is_array($value)) {
                $data[$key] = $value;
            }
            // Skip collections and complex objects
        }

        return $data;
    }

    // ── TableView methods for admin UI ────────────────────────────────

    public function compileTableColumns(): array
    {
        return [
            ['name' => 'id', 'label' => '#'],
            ['name' => 'action', 'label' => 'Action'],
            ['name' => 'entityType', 'label' => 'Entity'],
            ['name' => 'entityId', 'label' => 'Entity ID'],
            ['name' => 'user', 'label' => 'User'],
            ['name' => 'createdAt', 'label' => 'Date'],
        ];
    }

    public function prepareEntities($entities): array
    {
        $items = [];
        foreach ($entities as $activity) {
            $items[] = [
                'columns' => [
                    'id' => $activity->getId(),
                    'action' => $activity->action,
                    'entityType' => $activity->entityType,
                    'entityId' => $activity->entityId,
                    'user' => $activity->user ? $activity->user->getEmail() : 'system',
                    'createdAt' => $activity->getCreatedAt()->format('d.m.Y H:i'),
                ],
                'id' => $activity->getId(),
            ];
        }

        return $items;
    }
}
