<?php

declare(strict_types=1);

namespace Skeletor\Core\Login\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One enrolled authenticator, for any authenticatable entity.
 *
 * Keyed by (entityType, entityId) rather than living on the user table, so a delegate or a
 * donor can switch two-factor on without a schema change of their own — the same shape
 * MagicLinkToken already uses. Apps map vendor/dj_avolak/skeletor/src/Core/Login for Doctrine,
 * so this table appears without any config change.
 *
 * confirmedAt is the difference between "a QR code was displayed" and "the account is
 * actually protected": a row exists from the moment enrolment starts, but only a confirmed
 * row gates a login. Without that split, showing the setup page would lock the account out
 * of its own dashboard the moment the page was closed.
 */
#[ORM\Entity]
#[ORM\Table(name: 'two_factor_secret')]
#[ORM\UniqueConstraint(name: 'uniq_entity', columns: ['entityType', 'entityId'])]
class TwoFactorSecret
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    public string $entityType;

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    public int $entityId;

    /** SecretCipher envelope, never the bare base32 secret. */
    #[ORM\Column(type: Types::STRING, length: 512)]
    public string $secret;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    public \DateTime $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    public ?\DateTime $confirmedAt = null;

    /**
     * The TOTP counter a code was last accepted for. A code stays valid for its whole
     * period, so without this a code shoulder-surfed (or replayed from a proxy log) works
     * again for up to thirty seconds. Refusing a counter that has already been spent closes
     * that window.
     */
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    public ?string $lastUsedCounter = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    public int $failedAttempts = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    public ?\DateTime $lockedUntil = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmedAt !== null;
    }

    public function isLocked(?\DateTime $now = null): bool
    {
        return $this->lockedUntil !== null && $this->lockedUntil > ($now ?? new \DateTime());
    }

    public function hasSpent(int $counter): bool
    {
        return $this->lastUsedCounter !== null && (int) $this->lastUsedCounter >= $counter;
    }
}
