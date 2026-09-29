<?php

declare(strict_types=1);

namespace Skeletor\Tests\Core\Security;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Skeletor\Core\Security\EntityRegistry;
use Skeletor\Tests\Support\InMemoryAccountRepository;
use Skeletor\Tests\Support\TestAccount;

/**
 * The map from an entity type to the code that can load it.
 *
 * findByEmail() is the interesting part: the repositories in the wild disagree about how to
 * report a miss, and every framework caller now depends on that disagreement being absorbed
 * here rather than guessed at individually.
 */
#[CoversClass(EntityRegistry::class)]
final class EntityRegistryTest extends TestCase
{
    public function testResolvesRepositoriesAndClassesByType(): void
    {
        $repository = new InMemoryAccountRepository();
        $registry = new EntityRegistry();
        $registry->register('user', TestAccount::class, $repository);

        self::assertTrue($registry->has('user'));
        self::assertSame($repository, $registry->getRepository('user'));
        self::assertSame(TestAccount::class, $registry->getEntityClass('user'));
        self::assertSame(['user'], $registry->getRegisteredTypes());
    }

    public function testAnUnregisteredTypeIsReportedRatherThanGuessedAt(): void
    {
        $registry = new EntityRegistry();

        self::assertFalse($registry->has('delegate'));
        $this->expectException(\InvalidArgumentException::class);
        $registry->getRepository('delegate');
    }

    public function testTypeCanBeResolvedBackFromAnEntity(): void
    {
        $registry = new EntityRegistry();
        $registry->register('user', TestAccount::class, new InMemoryAccountRepository());

        self::assertSame('user', $registry->getTypeForEntity(new TestAccount()));
    }

    public function testFindByEmailReturnsTheAccountWhicheverConventionTheRepositoryFollows(): void
    {
        $account = new TestAccount(id: 3, email: 'found@example.com');

        foreach ([true, false] as $throwsOnMiss) {
            $registry = new EntityRegistry();
            $registry->register('user', TestAccount::class, new InMemoryAccountRepository($throwsOnMiss, $account));

            self::assertSame($account, $registry->findByEmail('user', 'found@example.com'));
        }
    }

    public function testFindByEmailIsNullForAMissWhicheverConventionTheRepositoryFollows(): void
    {
        // The reason this method exists. UserRepository and DelegateRepository throw
        // NotFoundException for an unknown address; DonorRepository returns null. Framework
        // code that picked either one would be wrong half the time — and the guards written
        // against the wrong assumption never fire.
        foreach ([true, false] as $throwsOnMiss) {
            $registry = new EntityRegistry();
            $registry->register('user', TestAccount::class, new InMemoryAccountRepository($throwsOnMiss));

            self::assertNull(
                $registry->findByEmail('user', 'nobody@example.com'),
                $throwsOnMiss ? 'throwing repository' : 'null-returning repository'
            );
        }
    }

    public function testLookupIsScopedToTheTypeAsked(): void
    {
        // A delegate and a user can hold the same address as easily as the same id; asking
        // for one must not answer with the other.
        $registry = new EntityRegistry();
        $registry->register('user', TestAccount::class, new InMemoryAccountRepository(true));
        $registry->register(
            'delegate',
            TestAccount::class,
            new InMemoryAccountRepository(true, new TestAccount(id: 9, email: 'shared@example.com'))
        );

        self::assertNull($registry->findByEmail('user', 'shared@example.com'));
        self::assertSame(9, $registry->findByEmail('delegate', 'shared@example.com')?->getId());
    }
}
