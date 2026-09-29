<?php

declare(strict_types=1);

namespace Skeletor\Tests\Support;

use Skeletor\Core\Login\Entity\ForgotPasswordToken;
use Skeletor\Core\Login\Repository\ForgotPasswordRepository;

/**
 * Password-reset tokens in memory.
 *
 * fetchAll() reproduces only what Login::verifyToken() asks of it — filter by entityId and
 * entityType, newest first, limit one — because that is the query whose correctness matters:
 * it is what stops one entity type from resolving another's token when the two share an id.
 */
class InMemoryForgotPasswordRepository extends ForgotPasswordRepository
{
    /** @var list<ForgotPasswordToken> */
    public array $tokens = [];

    private int $nextId = 1;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(private \DateTime $now = new \DateTime())
    {
    }

    public function create($data)
    {
        $token = new ForgotPasswordToken();
        $token->id = $this->nextId++;
        $token->entityId = (string) $data['entityId'];
        $token->entityType = (string) $data['entityType'];
        $token->token = $data['token'];
        $token->createdAt = clone $this->now;
        $token->updatedAt = clone $this->now;

        $this->tokens[] = $token;

        return $token;
    }

    public function fetchAll($params = [], $limit = null, $order = null, $returnArray = null, $offset = null): array
    {
        $matches = array_values(array_filter($this->tokens, static fn (ForgotPasswordToken $t): bool =>
            (!isset($params['entityId']) || (string) $t->entityId === (string) $params['entityId'])
            && (!isset($params['entityType']) || $t->entityType === (string) $params['entityType'])));

        usort($matches, static fn ($a, $b) => $b->createdAt <=> $a->createdAt);

        return $limit ? array_slice($matches, 0, $limit) : $matches;
    }

    public function resetToken($id)
    {
        foreach ($this->tokens as $token) {
            if ($token->getId() === $id) {
                $token->resetToken();

                return;
            }
        }
    }

    /** Backdate the newest token, for testing the reset-link lifetime. */
    public function age(string $modifier): void
    {
        $newest = $this->tokens[array_key_last($this->tokens)];
        $newest->createdAt = (clone $newest->createdAt)->modify($modifier);
    }
}
