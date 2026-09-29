<?php

declare(strict_types=1);

namespace Skeletor\Tests\Support;

use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Core\Login\Repository\LoginRepositoryInterface;

/**
 * An account store in an array.
 *
 * $throwOnMiss switches between the two conventions the real repositories actually use —
 * UserRepository and DelegateRepository throw NotFoundException for an unknown address,
 * DonorRepository returns null. Framework code has to survive both, so the tests exercise
 * both rather than picking whichever is convenient.
 */
class InMemoryAccountRepository implements LoginRepositoryInterface
{
    /** @var array<string, TestAccount> keyed by email */
    private array $byEmail = [];

    public int $loginInfoUpdates = 0;

    /** @var array<int|string, string> */
    public array $passwordUpdates = [];

    public function __construct(private bool $throwOnMiss = true, TestAccount ...$accounts)
    {
        foreach ($accounts as $account) {
            $this->add($account);
        }
    }

    public function add(TestAccount $account): self
    {
        $this->byEmail[strtolower($account->getEmail())] = $account;

        return $this;
    }

    public function findByEmail(string $email)
    {
        $account = $this->byEmail[strtolower($email)] ?? null;

        if (!$account && $this->throwOnMiss) {
            throw new NotFoundException();
        }

        return $account;
    }

    public function getById($id)
    {
        foreach ($this->byEmail as $account) {
            if ((string) $account->getId() === (string) $id) {
                return $account;
            }
        }

        return null;
    }

    public function updatePassword($userId, $password)
    {
        $this->passwordUpdates[$userId] = $password;
    }

    public function updateLoginInfo($model)
    {
        $this->loginInfoUpdates++;
    }
}
