<?php
declare(strict_types=1);

namespace Skeletor\Core\Login\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Skeletor\Core\Login\Entity\MagicLinkToken;
use Skeletor\Core\Login\Exception\InvalidCredentials;

/**
 * Repository for Magic Link Tokens
 *
 * The token column holds a SHA-256 of the token, never the token itself. A magic link is a
 * bearer credential — anyone holding one is logged in as its owner — so storing it in the
 * clear means a read-only leak of this table (a backup, a replica, an injection that can
 * only SELECT) is account takeover for everyone with a live link. Hashing makes the stored
 * value useless on its own.
 *
 * A plain SHA-256 rather than a password hash is the right choice here, and only here: the
 * token is 64 bytes from random_bytes, so there is no dictionary to attack and nothing for a
 * work factor to slow down. Passwords still go through password_hash().
 *
 * Tokens issued before this change no longer resolve. They expire within fifteen minutes, so
 * the whole cost is that anyone mid-login when it deploys asks for another link.
 */
class MagicLinkTokenRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private \DateTime $dt
    ) {}

    /**
     * Create a new magic link token.
     *
     * Takes the plaintext token and stores its hash; the caller keeps the plaintext for the
     * email, and after this call nothing can recover it from the database.
     */
    public function create(
        string $token,
        string $entityType,
        int $entityId,
        int $expiryMinutes = 15,
        bool $rememberMe = false
    ): MagicLinkToken
    {
        $entity = new MagicLinkToken();
        $entity->token = self::hash($token);
        $entity->entityType = $entityType;
        $entity->entityId = $entityId;
        $entity->createdAt = clone $this->dt;
        $entity->expiresAt = (clone $this->dt)->modify(sprintf('+%d minutes', $expiryMinutes));
        $entity->ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $entity->rememberMe = $rememberMe;

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    /**
     * Find token by the plaintext token string as it appears in the emailed link.
     */
    public function findByToken(string $token): ?MagicLinkToken
    {
        return $this->entityManager
            ->getRepository(MagicLinkToken::class)
            ->findOneBy(['token' => self::hash($token)]);
    }

    /**
     * Verify token and return entity data
     *
     * @throws InvalidCredentials
     */
    public function verifyToken(string $token): array
    {
        $tokenEntity = $this->findByToken($token);

        if (!$tokenEntity) {
            throw new InvalidCredentials('Invalid or expired magic link token');
        }

        if (!$tokenEntity->isUsable()) {
            throw new InvalidCredentials('This magic link has already been used or has expired');
        }

        return [
            'tokenId' => $tokenEntity->getId(),
            'entityType' => $tokenEntity->entityType,
            'entityId' => $tokenEntity->entityId,
        ];
    }

    /**
     * Invalidate token after use
     */
    public function invalidateToken(int $tokenId): void
    {
        $token = $this->entityManager->find(MagicLinkToken::class, $tokenId);

        if ($token) {
            $token->markAsUsed();
            $this->entityManager->flush();
        }
    }

    /**
     * Clean up expired tokens (can be run via cron)
     */
    public function cleanupExpiredTokens(): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete(MagicLinkToken::class, 't')
            ->where('t.expiresAt < :now')
            ->setParameter('now', $this->dt);

        return $qb->getQuery()->execute();
    }

    /**
     * Invalidate all tokens for a specific entity
     */
    public function invalidateAllForEntity(string $entityType, int $entityId): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->update(MagicLinkToken::class, 't')
            ->set('t.isValid', ':false')
            ->where('t.entityType = :type')
            ->andWhere('t.entityId = :id')
            ->andWhere('t.isValid = :true')
            ->setParameter('false', false)
            ->setParameter('true', true)
            ->setParameter('type', $entityType)
            ->setParameter('id', $entityId);

        $qb->getQuery()->execute();
    }

    /**
     * Was a link issued for this account since $since?
     *
     * Counts tokens whatever their current state, because invalidateAllForEntity() runs
     * immediately before each new issue — asking only about valid tokens would always answer
     * "no" and make the cooldown in MagicLinkService a no-op.
     */
    public function hasRequestSince(string $entityType, int $entityId, \DateTimeInterface $since): bool
    {
        $count = $this->entityManager->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(MagicLinkToken::class, 't')
            ->where('t.entityType = :type')
            ->andWhere('t.entityId = :id')
            ->andWhere('t.createdAt > :since')
            ->setParameter('type', $entityType)
            ->setParameter('id', $entityId)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    /**
     * The stored form of a token. Public so a test or a migration can reproduce it without
     * guessing at the algorithm.
     */
    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
