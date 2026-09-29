<?php

declare(strict_types=1);

namespace Skeletor\Tests\Support;

use Skeletor\Core\Login\Entity\TwoFactorSecret;
use Skeletor\Core\Login\Repository\TwoFactorSecretRepository;

/**
 * The two-factor store, in memory.
 *
 * Extends the real repository so the service is exercised against the real type, and
 * overrides every method — the parent constructor is skipped on purpose, since its only job
 * is to hold a Doctrine EntityManager none of these tests have.
 */
class InMemoryTwoFactorSecretRepository extends TwoFactorSecretRepository
{
    /** @var array<string, TwoFactorSecret> */
    private array $records = [];

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(private \DateTime $now = new \DateTime())
    {
    }

    public function find(string $entityType, int $entityId): ?TwoFactorSecret
    {
        return $this->records[$entityType . ':' . $entityId] ?? null;
    }

    public function startEnrolment(string $entityType, int $entityId, string $encryptedSecret): TwoFactorSecret
    {
        $record = $this->find($entityType, $entityId) ?? new TwoFactorSecret();
        $record->entityType = $entityType;
        $record->entityId = $entityId;
        $record->secret = $encryptedSecret;
        $record->createdAt = clone $this->now;
        $record->confirmedAt = null;
        $record->lastUsedCounter = null;
        $record->failedAttempts = 0;
        $record->lockedUntil = null;

        return $this->records[$entityType . ':' . $entityId] = $record;
    }

    public function confirm(TwoFactorSecret $record, int $counter): void
    {
        $record->confirmedAt = clone $this->now;
        $record->lastUsedCounter = (string) $counter;
        $record->failedAttempts = 0;
        $record->lockedUntil = null;
    }

    public function recordSuccess(TwoFactorSecret $record, int $counter): void
    {
        $record->lastUsedCounter = (string) $counter;
        $record->failedAttempts = 0;
        $record->lockedUntil = null;
    }

    public function recordFailure(TwoFactorSecret $record, int $maxAttempts, int $lockSeconds): void
    {
        $record->failedAttempts++;

        if ($record->failedAttempts >= $maxAttempts) {
            $record->lockedUntil = (clone $this->now)->modify(sprintf('+%d seconds', $lockSeconds));
        }
    }

    public function delete(string $entityType, int $entityId): void
    {
        unset($this->records[$entityType . ':' . $entityId]);
    }
}
