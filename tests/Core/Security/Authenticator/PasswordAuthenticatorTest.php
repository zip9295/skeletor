<?php

declare(strict_types=1);

namespace Skeletor\Tests\Core\Security\Authenticator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Core\Security\Authentication\MagicLinkCredentials;
use Skeletor\Core\Security\Authentication\PasswordCredentials;
use Skeletor\Core\Security\Authenticator\PasswordAuthenticator;
use Skeletor\Core\Security\EntityRegistry;
use Skeletor\Core\Login\Exception\InvalidCredentials;
use Skeletor\Tests\Support\InMemoryAccountRepository;
use Skeletor\Tests\Support\Policies;
use Skeletor\Tests\Support\TestAccount;

/**
 * The password door.
 *
 * Written but never called until the login controller was rewritten to use it, so this is
 * the first thing that has ever exercised it.
 */
#[CoversClass(PasswordAuthenticator::class)]
final class PasswordAuthenticatorTest extends TestCase
{
    private InMemoryAccountRepository $repository;

    private EntityRegistry $registry;

    protected function setUp(): void
    {
        $this->repository = new InMemoryAccountRepository(true, TestAccount::withPassword('correct horse', 4));
        $this->registry = new EntityRegistry();
        $this->registry->register('user', TestAccount::class, $this->repository);
    }

    private function authenticator(): PasswordAuthenticator
    {
        return new PasswordAuthenticator($this->registry, Policies::legacyDefault());
    }

    private function credentials(string $email = 'someone@example.com', string $password = 'correct horse'): PasswordCredentials
    {
        return new PasswordCredentials($email, $password, 'user');
    }

    public function testTheRightPasswordAuthenticates(): void
    {
        $entity = $this->authenticator()->authenticate($this->credentials());

        self::assertSame(4, $entity->getId());
    }

    public function testASuccessfulLoginIsStamped(): void
    {
        $this->authenticator()->authenticate($this->credentials());

        self::assertSame(1, $this->repository->loginInfoUpdates);
    }

    public function testTheWrongPasswordIsRefused(): void
    {
        $this->expectException(InvalidCredentials::class);

        $this->authenticator()->authenticate($this->credentials(password: 'wrong horse'));
    }

    public function testAFailedLoginIsNotStamped(): void
    {
        try {
            $this->authenticator()->authenticate($this->credentials(password: 'wrong horse'));
        } catch (InvalidCredentials) {
            // expected
        }

        self::assertSame(0, $this->repository->loginInfoUpdates);
    }

    public function testAnUnknownAddressIsReportedAsNotFound(): void
    {
        // Not as "entity must implement AuthenticatableInterface", which is what a null from
        // a null-returning repository used to produce.
        $this->expectException(NotFoundException::class);

        $this->authenticator()->authenticate($this->credentials(email: 'nobody@example.com'));
    }

    public function testAnUnknownAddressIsAlsoNotFoundForANullReturningRepository(): void
    {
        $registry = new EntityRegistry();
        $registry->register('user', TestAccount::class, new InMemoryAccountRepository(false));

        $this->expectException(NotFoundException::class);

        (new PasswordAuthenticator($registry, Policies::legacyDefault()))->authenticate($this->credentials(email: 'nobody@example.com'));
    }

    public function testAnAccountWithNoPasswordCannotBeEnteredWithOne(): void
    {
        // Delegates and donors are magic-link only; getAuthPassword() is null for them, and
        // password_verify() against null is a TypeError rather than a refusal.
        $registry = new EntityRegistry();
        $registry->register('user', TestAccount::class, new InMemoryAccountRepository(true, new TestAccount(id: 8)));

        $this->expectException(InvalidCredentials::class);

        (new PasswordAuthenticator($registry, Policies::legacyDefault()))->authenticate($this->credentials());
    }

    public function testADeactivatedAccountIsRefusedEvenWithTheRightPassword(): void
    {
        $account = TestAccount::withPassword('correct horse', 4);
        $account->deactivate();
        $registry = new EntityRegistry();
        $registry->register('user', TestAccount::class, new InMemoryAccountRepository(true, $account));

        $this->expectException(InvalidCredentials::class);

        (new PasswordAuthenticator($registry, Policies::legacyDefault()))->authenticate($this->credentials());
    }

    public function testEmptyCredentialsAreRefusedWithoutALookup(): void
    {
        foreach ([['', 'x'], ['someone@example.com', '']] as [$email, $password]) {
            try {
                $this->authenticator()->authenticate(new PasswordCredentials($email, $password, 'user'));
                self::fail('empty credentials should not authenticate');
            } catch (InvalidCredentials) {
                self::assertTrue(true);
            }
        }
    }

    public function testItOnlyHandlesPasswordCredentials(): void
    {
        $authenticator = $this->authenticator();

        self::assertTrue($authenticator->supports($this->credentials()));
        self::assertFalse($authenticator->supports(new MagicLinkCredentials(str_repeat('a', 64), 'user')));
    }

    public function testTheEntityTypeDecidesWhichStoreIsAsked(): void
    {
        $registry = new EntityRegistry();
        $registry->register('user', TestAccount::class, new InMemoryAccountRepository(true));
        $registry->register(
            'delegate',
            TestAccount::class,
            new InMemoryAccountRepository(true, TestAccount::withPassword('correct horse', 11))
        );

        $entity = (new PasswordAuthenticator($registry, Policies::legacyDefault()))->authenticate(
            new PasswordCredentials('someone@example.com', 'correct horse', 'delegate')
        );

        self::assertSame(11, $entity->getId());
    }
}
