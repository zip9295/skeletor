<?php
namespace Skeletor\Core\Service;

use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Activity\Entity\Activity as ActivityEntity;
use Skeletor\Core\Activity\Service\Activity;
use Skeletor\Core\Filter\FilterInterface as Filter;
use Skeletor\Core\Repository\RepositoryInterface as Repository;
use Skeletor\Core\Service\Contracts\CrudServiceInterface;
use Skeletor\Tenant\Repository\TenantRepositoryInterface as TenantRepo;
use Skeletor\User\Service\Session;

abstract class CrudService implements CrudServiceInterface
{
    const TENANT_ID_FIELD_NAME = 'tenantId';
    const APPLY_TENANT_FILTER = false;

    /**
     * Activity tracking is on for every registered entity. Set to false in a child class to opt a
     * single service out (a high-write log-like entity, say) rather than to opt others in.
     */
    const TRACK_ACTIVITY = true;

    /**
     * Name recorded in the activity log. Leave empty and it is derived from the repository's
     * entity class short name, lowercased — 'user' for Skeletor\User\Entity\User. Override only
     * when a service needs a name that does not match its entity.
     */
    const ENTITY_TYPE = '';

    /** Entities whose own writes must never be logged, or the log would log itself. */
    private const UNTRACKED_ENTITIES = [ActivityEntity::class];

    /** @var array<string, string|null> resolved entity type per service class */
    private static array $entityTypeCache = [];

    /**
     * @param Repository $repo
     * @param Session $loginService
     * @param Logger $logger
     * @param TenantRepo|null $tenant
     * @param Filter|null $filter
     * @param Activity|null $activity
     */
    public function __construct(
        protected Repository $repo, protected Session $loginService, protected Logger $logger, protected ?TenantRepo $tenant,
        protected ?Filter $filter, protected ?Activity $activity = null,
    ) {}

    public function getUserSession()
    {
        return $this->loginService;
    }
    /**
     * Apply (or not) tenant filter to data set.
     *
     * @param $params
     * @return mixed
     */
    protected function applyTenantFilter($params)
    {
        if (static::APPLY_TENANT_FILTER && !$this->loginService->isAdminLoggedIn()
            && $this->loginService->getLoggedInUserId() && $this->loginService->getLoggedInTenantId()) {
            $params[static::TENANT_ID_FIELD_NAME] = $this->loginService->getLoggedInTenantId();
        }
        return $params;
    }

    public function getEntities($filter = [], $limit = null, $order = null, $offset = null)
    {
        $data = [];
        $filter = $this->applyTenantFilter($filter);
        foreach ($this->repo->fetchAll($filter, $limit, $order, null, $offset) as $entity) {
            $data[] = $entity;
        }

        return $data;
    }

    public function getFilterData($params = [], $limit = null, $order = null, $property = 'name')
    {
        $data = [];
        $params = $this->applyTenantFilter($params);
        foreach ($this->repo->fetchAll($params, $limit, $order) as $entity) {
            if (!property_exists($entity, $property)) {
                throw new \Exception(sprintf(
                    'Model %s does not have a %s method.', get_class($entity), $property));
            }
            $data[$entity->id] = $entity->{$property};
        }

        return $data;
    }

    public function getEntityData($id)
    {
        return [
            'id' => $id,
        ];
    }

    public function getById($id)
    {
        return $this->repo->getById($id);
    }

    public function getTenants()
    {
        $tenantFilter = [];
        if (static::APPLY_TENANT_FILTER && !$this->loginService->isAdminLoggedIn()) {
            $tenantFilter = [static::TENANT_ID_FIELD_NAME => $this->loginService->getLoggedInTenantId()];
        }

        return $this->tenant->fetchAll($tenantFilter);
    }

    public function update(array $data)
    {
        if ($this->filter) {
            $data = $this->filter->filter($data);
        }

        // Capture old state before update for activity tracking
        $oldSnapshot = null;
        $track = $this->tracksActivity();
        if ($track && isset($data['id'])) {
            try {
                $oldEntity = $this->repo->getById($data['id']);
                $oldSnapshot = Activity::entityToArray($oldEntity);
            } catch (\Throwable) {}
        }

        $model = $this->repo->update($data);

        if ($track) {
            $this->recordActivity('update', $model, $oldSnapshot);
        }

        return $model;
    }

    public function create(array $data)
    {
        if ($this->filter) {
            $data = $this->filter->filter($data);
        }
        $model = $this->repo->create($data);

        if ($this->tracksActivity()) {
            $this->recordActivity('create', $model);
        }

        return $model;
    }

    public function delete($id)
    {
        // Capture state before deletion for activity tracking
        $oldSnapshot = null;
        $entityId = null;
        if ($this->tracksActivity()) {
            try {
                $oldEntity = $this->repo->getById($id);
                $oldSnapshot = Activity::entityToArray($oldEntity);
                $entityId = $oldEntity->getId();
            } catch (\Throwable) {}
        }

        $this->repo->delete($id);

        $entityType = $this->activityEntityType();
        if ($oldSnapshot !== null && $entityId !== null && $entityType !== null) {
            try {
                $user = $this->getLoggedInUser();
                $this->activity->record('delete', $entityType, $entityId, null, $oldSnapshot, $user);
            } catch (\Throwable $e) {
                // Same rule as create/update: logging a change must never fail the change itself.
                $this->logger->warning('Activity tracking failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Whether writes from this service should be logged.
     *
     * Requires tracking to be enabled, the Activity service to actually be wired in, and a
     * resolvable entity type that is not itself an activity record.
     */
    protected function tracksActivity(): bool
    {
        return static::TRACK_ACTIVITY
            && $this->activity !== null
            && $this->activityEntityType() !== null;
    }

    /**
     * The name this service's entity is logged under.
     *
     * Uses ENTITY_TYPE when a service sets one, otherwise derives it from the repository's ENTITY
     * constant so that every registered entity is covered without each service having to declare
     * itself. Returns null when there is nothing sensible to record against — an untracked entity,
     * or a repository that declares no entity at all.
     */
    public function activityEntityType(): ?string
    {
        $key = static::class;
        if (array_key_exists($key, self::$entityTypeCache)) {
            return self::$entityTypeCache[$key];
        }

        if (static::ENTITY_TYPE !== '') {
            return self::$entityTypeCache[$key] = static::ENTITY_TYPE;
        }

        $repoClass = $this->repo::class;
        if (!defined($repoClass . '::ENTITY')) {
            return self::$entityTypeCache[$key] = null;
        }

        $entityClass = constant($repoClass . '::ENTITY');
        if (!is_string($entityClass) || in_array($entityClass, self::UNTRACKED_ENTITIES, true)) {
            return self::$entityTypeCache[$key] = null;
        }

        // Lowercased short name, matching how Activity::resolveEntityClass() looks entities back up.
        $short = substr((string) strrchr('\\' . $entityClass, '\\'), 1);

        return self::$entityTypeCache[$key] = strtolower($short);
    }

    /**
     * Record an activity entry for create/update operations.
     */
    private function recordActivity(string $action, $model, ?array $oldSnapshot = null): void
    {
        $entityType = $this->activityEntityType();
        if ($entityType === null) {
            return;
        }

        try {
            $newSnapshot = Activity::entityToArray($model);
            $user = $this->getLoggedInUser();
            $entityId = $model->getId();
            $this->activity->record($action, $entityType, $entityId, $newSnapshot, $oldSnapshot, $user);
        } catch (\Throwable $e) {
            // Activity tracking should never break the main operation
            $this->logger->warning('Activity tracking failed: ' . $e->getMessage());
        }
    }

    /**
     * Get the currently logged-in User entity (or null for CLI/system operations).
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

    public function updateField($field, $value, $entityId)
    {
        return $this->repo->updateField($field, $value, $entityId);
    }

    public function parseErrors()
    {
        $errors = [];
        foreach ($this->filter->getErrors() as $key => $messages) {
            foreach ($messages as $message) {
                $errors[] = ['message' => $message];
            }
        }
        return $errors;
    }

    public function getPrimaryKeyIdentifier()
    {
        return $this->repo->getPrimaryKeyIdentifier();
    }

    public function getRepository()
    {
        return $this->repo;
    }

    public function getSearchableColumns(): array
    {
        return $this->repo->getSearchableColumns();
    }

    public function searchByField(array $searchFilter, array $andFilters = [], $limit = null, $order = null, $offset = null)
    {
        return $this->repo->searchByField($searchFilter, $andFilters, $limit, $order, $offset);
    }
}