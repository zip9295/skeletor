<?php

declare(strict_types=1);

namespace Skeletor\Tests\Support;

use Skeletor\Core\Login\Entity\MagicLinkToken;
use Skeletor\Core\Login\Repository\MagicLinkTokenRepository;

/**
 * Magic-link tokens in memory, hashed exactly as the real repository hashes them.
 *
 * Reusing the parent's hash() rather than storing plaintext is the point: a fake that keyed
 * on the raw token would still pass if the hashing were removed from production code, which
 * is precisely the regression worth catching.
 */
class InMemoryMagicLinkTokenRepository extends MagicLinkTokenRepository
{
    /** @var array<string, MagicLinkToken> keyed by stored (hashed) token */
    public array $tokens = [];

    private int $nextId = 1;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(private \DateTime $now = new \DateTime())
    {
    }

    public function create(
        string $token,
        string $entityType,
        int $entityId,
        int $expiryMinutes = 15,
        bool $rememberMe = false
    ): MagicLinkToken {
        $entity = new MagicLinkToken();
        $entity->token = self::hash($token);
        $entity->entityType = $entityType;
        $entity->entityId = $entityId;
        $entity->createdAt = clone $this->now;
        $entity->expiresAt = (clone $this->now)->modify(sprintf('+%d minutes', $expiryMinutes));
        $entity->rememberMe = $rememberMe;

        // The entity generates its id in the database; here it is assigned by hand so
        // invalidateToken() has something to look up.
        $reflection = new \ReflectionProperty(MagicLinkToken::class, 'id');
        $reflection->setValue($entity, $this->nextId++);

        return $this->tokens[$entity->token] = $entity;
    }

    public function findByToken(string $token): ?MagicLinkToken
    {
        return $this->tokens[self::hash($token)] ?? null;
    }

    public function invalidateToken(int $tokenId): void
    {
        foreach ($this->tokens as $token) {
            if ($token->getId() === $tokenId) {
                $token->markAsUsed();

                return;
            }
        }
    }

    public function invalidateAllForEntity(string $entityType, int $entityId): void
    {
        foreach ($this->tokens as $token) {
            if ($token->entityType === $entityType && $token->entityId === $entityId) {
                $token->invalidate();
            }
        }
    }

    public function hasRequestSince(string $entityType, int $entityId, \DateTimeInterface $since): bool
    {
        foreach ($this->tokens as $token) {
            if ($token->entityType === $entityType && $token->entityId === $entityId && $token->createdAt > $since) {
                return true;
            }
        }

        return false;
    }

    public function cleanupExpiredTokens(): int
    {
        $removed = 0;
        foreach ($this->tokens as $key => $token) {
            if ($token->isExpired()) {
                unset($this->tokens[$key]);
                $removed++;
            }
        }

        return $removed;
    }

    /** The plaintext tokens issued so far, in order, for tests that need to follow a link. */
    public function issuedFor(string $entityType, int $entityId): array
    {
        $out = [];
        foreach ($this->tokens as $token) {
            if ($token->entityType === $entityType && $token->entityId === $entityId) {
                $out[] = $token;
            }
        }

        return $out;
    }
}
