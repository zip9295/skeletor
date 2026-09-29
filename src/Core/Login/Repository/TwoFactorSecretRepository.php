<?php

declare(strict_types=1);

namespace Skeletor\Core\Login\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Skeletor\Core\Login\Entity\TwoFactorSecret;

/**
 * Storage for enrolled authenticators.
 *
 * Persistence only — whether a code is acceptable, how many failures are too many and how
 * long a lockout lasts are policy, and policy lives in TwoFactorService. Mirrors
 * MagicLinkTokenRepository, including the injected "now" so time-dependent behaviour is
 * testable without sleeping.
 */
class TwoFactorSecretRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private \DateTime $dt
    ) {}

    public function find(string $entityType, int $entityId): ?TwoFactorSecret
    {
        return $this->entityManager
            ->getRepository(TwoFactorSecret::class)
            ->findOneBy(['entityType' => $entityType, 'entityId' => $entityId]);
    }

    /**
     * Begin (or restart) enrolment.
     *
     * Restarting deliberately overwrites an existing unconfirmed row and clears any
     * confirmation on it: re-enrolling means the old secret is being replaced, and leaving
     * the previous one confirmed would accept codes from a device the user is trying to
     * walk away from.
     */
    public function startEnrolment(string $entityType, int $entityId, string $encryptedSecret): TwoFactorSecret
    {
        $record = $this->find($entityType, $entityId) ?? new TwoFactorSecret();

        $record->entityType = $entityType;
        $record->entityId = $entityId;
        $record->secret = $encryptedSecret;
        $record->createdAt = clone $this->dt;
        $record->confirmedAt = null;
        $record->lastUsedCounter = null;
        $record->failedAttempts = 0;
        $record->lockedUntil = null;

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $record;
    }

    public function confirm(TwoFactorSecret $record, int $counter): void
    {
        $record->confirmedAt = clone $this->dt;
        $record->lastUsedCounter = (string) $counter;
        $record->failedAttempts = 0;
        $record->lockedUntil = null;

        $this->entityManager->flush();
    }

    public function recordSuccess(TwoFactorSecret $record, int $counter): void
    {
        $record->lastUsedCounter = (string) $counter;
        $record->failedAttempts = 0;
        $record->lockedUntil = null;

        $this->entityManager->flush();
    }

    /**
     * Count a wrong code, locking the record once $maxAttempts is reached.
     *
     * The counter is not reset on lockout: each further failure after the lock expires
     * re-locks immediately, so an attacker gets one guess per lockout window rather than a
     * fresh batch. Only a success clears it.
     */
    public function recordFailure(TwoFactorSecret $record, int $maxAttempts, int $lockSeconds): void
    {
        $record->failedAttempts++;

        if ($record->failedAttempts >= $maxAttempts) {
            $record->lockedUntil = (clone $this->dt)->modify(sprintf('+%d seconds', $lockSeconds));
        }

        $this->entityManager->flush();
    }

    public function delete(string $entityType, int $entityId): void
    {
        $record = $this->find($entityType, $entityId);
        if (!$record) {
            return;
        }

        $this->entityManager->remove($record);
        $this->entityManager->flush();
    }
}
