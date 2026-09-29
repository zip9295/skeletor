<?php
declare(strict_types=1);

namespace Skeletor\Core\Login\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Magic Link Token Entity
 */
#[ORM\Entity]
#[ORM\Table(name: 'magic_link_token')]
#[ORM\Index(name: 'idx_token', columns: ['token'])]
#[ORM\Index(name: 'idx_entity', columns: ['entityType', 'entityId'])]
#[ORM\Index(name: 'idx_valid', columns: ['isValid', 'expiresAt'])]
class MagicLinkToken
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    public string $token;

    #[ORM\Column(type: Types::STRING, length: 50)]
    public string $entityType;

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    public int $entityId;

    #[ORM\Column(type: Types::BOOLEAN)]
    public bool $isValid = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    public \DateTime $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    public \DateTime $expiresAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    public ?\DateTime $usedAt = null;

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    public ?string $ipAddress = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => 0])]
    public bool $rememberMe = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime();
    }

    public function isUsable(): bool
    {
        return $this->isValid && !$this->isExpired() && $this->usedAt === null;
    }

    public function markAsUsed(): void
    {
        $this->usedAt = new \DateTime();
        $this->isValid = false;
    }

    public function invalidate(): void
    {
        $this->isValid = false;
    }
}
