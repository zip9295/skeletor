<?php

declare(strict_types=1);

namespace Skeletor\Tests\Core\Security\Authenticator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Skeletor\Core\Security\Authentication\MagicLinkCredentials;
use Skeletor\Core\Security\Authentication\PasswordCredentials;
use Skeletor\Core\Security\Authenticator\AuthenticatorRegistry;
use Skeletor\Core\Security\Authenticator\MagicLinkAuthenticator;
use Skeletor\Core\Security\Authenticator\PasswordAuthenticator;
use Skeletor\Core\Security\EntityRegistry;
use Skeletor\Core\Security\AuthPolicy;
use Skeletor\Core\Login\Exception\AuthMethodDisabled;
use Skeletor\Core\Login\Exception\InvalidCredentials;
use Skeletor\Tests\Support\InMemoryAccountRepository;
use Skeletor\Tests\Support\InMemoryMagicLinkTokenRepository;
use Skeletor\Tests\Support\Policies;
use Skeletor\Tests\Support\TestAccount;

/**
 * Following a link, and the routing that decides which authenticator sees it.
 *
 * A magic link is a bearer credential: holding one is being logged in. So every refusal here
 * matters more than the acceptance does.
 */
#[CoversClass(MagicLinkAuthenticator::class)]
#[CoversClass(AuthenticatorRegistry::class)]
final class MagicLinkAuthenticatorTest extends TestCase
{
    private InMemoryMagicLinkTokenRepository $tokens;
    private EntityRegistry $registry;
    private TestAccount $account;

    protected function setUp(): void
    {
        $this->tokens = new InMemoryMagicLinkTokenRepository();
        $this->account = new TestAccount(id: 5, email: 'someone@example.com');
        $this->registry = new EntityRegistry();
        $this->registry->register('user', TestAccount::class, new InMemoryAccountRepository(true, $this->account));
        $this->registry->register(
            'delegate',
            TestAccount::class,
            new InMemoryAccountRepository(true, new TestAccount(id: 5, email: 'del@example.com'))
        );
    }

    private function authenticator(): MagicLinkAuthenticator
    {
        return new MagicLinkAuthenticator($this->registry, Policies::legacyDefault(), $this->tokens);
    }

    private function issue(string $entityType = 'user', int $entityId = 5): string
    {
        $token = bin2hex(random_bytes(32));
        $this->tokens->create($token, $entityType, $entityId);

        return $token;
    }

    public function testAValidLinkResolvesToItsAccount(): void
    {
        self::assertSame(5, $this->authenticator()->authenticate(new MagicLinkCredentials($this->issue(), 'user'))->getId());
    }

    public function testFollowingALinkSpendsIt(): void
    {
        $token = $this->issue();

        $this->authenticator()->authenticate(new MagicLinkCredentials($token, 'user'));

        self::assertFalse($this->tokens->findByToken($token)->isUsable());
    }

    public function testALinkCannotBeFollowedTwice(): void
    {
        $token = $this->issue();
        $this->authenticator()->authenticate(new MagicLinkCredentials($token, 'user'));

        $this->expectException(InvalidCredentials::class);
        $this->authenticator()->authenticate(new MagicLinkCredentials($token, 'user'));
    }

    public function testALinkForOneKindOfAccountIsNotValidForAnother(): void
    {
        // Both are id 5. Without the type check the token would resolve against whichever
        // repository the URL happens to name.
        $token = $this->issue('delegate');

        $this->expectException(InvalidCredentials::class);
        $this->authenticator()->authenticate(new MagicLinkCredentials($token, 'user'));
    }

    public function testAnExpiredLinkIsRefused(): void
    {
        $token = bin2hex(random_bytes(32));
        $this->tokens->create($token, 'user', 5, expiryMinutes: -1);

        $this->expectException(InvalidCredentials::class);
        $this->authenticator()->authenticate(new MagicLinkCredentials($token, 'user'));
    }

    public function testAnUnknownTokenIsRefused(): void
    {
        $this->expectException(InvalidCredentials::class);

        $this->authenticator()->authenticate(new MagicLinkCredentials(str_repeat('a', 64), 'user'));
    }

    public function testAnAccountDeactivatedAfterTheLinkWasSentCannotUseIt(): void
    {
        // The window between issuing and following is exactly where a suspension lands.
        $token = $this->issue();
        $this->account->deactivate();

        $this->expectException(InvalidCredentials::class);
        $this->authenticator()->authenticate(new MagicLinkCredentials($token, 'user'));
    }

    public function testTheRegistryRoutesEachKindOfCredentialToItsOwnAuthenticator(): void
    {
        $registry = new AuthenticatorRegistry(
            Policies::legacyDefault(),
            new PasswordAuthenticator($this->registry, Policies::legacyDefault()),
            $this->authenticator(),
        );

        self::assertSame(5, $registry->authenticate(new MagicLinkCredentials($this->issue(), 'user'))->getId());
    }

    public function testTheRegistryRefusesAMethodTheApplicationHasSwitchedOff(): void
    {
        // The backstop. LoginController 404s a disabled method's endpoint, but that gate is one
        // route table and one ACL file away from being wrong; this is the check that cannot be
        // bypassed, because every path to a session goes through it.
        $registry = new AuthenticatorRegistry(
            Policies::with([AuthPolicy::METHOD_MAGIC_LINK], AuthPolicy::METHOD_MAGIC_LINK),
            new PasswordAuthenticator($this->registry, Policies::legacyDefault()),
            $this->authenticator(),
        );

        $this->expectException(AuthMethodDisabled::class);
        $registry->authenticate(new PasswordCredentials('someone@example.com', 'correct horse', 'user'));
    }

    public function testTheRegistryRefusesToBeBuiltWithAnEnabledMethodThatHasNoAuthenticator(): void
    {
        // A method switched on with nothing behind it would render a login form that fails on
        // submit. Better to refuse when the container is built.
        $this->expectException(\LogicException::class);

        new AuthenticatorRegistry(
            Policies::with([AuthPolicy::METHOD_SSO], AuthPolicy::METHOD_SSO),
            new PasswordAuthenticator($this->registry, Policies::legacyDefault()),
            $this->authenticator(),
        );
    }

    public function testTheRegistryRefusesCredentialsNothingHandles(): void
    {
        $registry = new AuthenticatorRegistry(
            Policies::legacyDefault(),
            new PasswordAuthenticator($this->registry, Policies::legacyDefault()),
            $this->authenticator(),
        );
        $unsupported = new class implements \Skeletor\Core\Security\Authentication\CredentialsInterface {
            public function getType(): string { return 'carrier-pigeon'; }
            public function getEntityType(): string { return 'user'; }
            public function isValid(): bool { return true; }
        };

        $this->expectException(\RuntimeException::class);
        $registry->authenticate($unsupported);
    }

    public function testSsoCredentialsAreUnsupportedUntilAnAuthenticatorIsWiredForThem(): void
    {
        // The registry takes the social authenticator optionally, and most apps wire none.
        $registry = new AuthenticatorRegistry(
            Policies::legacyDefault(),
            new PasswordAuthenticator($this->registry, Policies::legacyDefault()),
            $this->authenticator(),
        );

        self::assertNull($registry->getAuthenticator(\Skeletor\Core\Security\Authenticator\SsoAuthenticator::class));
        self::assertNotNull($registry->getAuthenticator(PasswordAuthenticator::class));
    }

    public function testPasswordCredentialsAreNotHandledHere(): void
    {
        self::assertFalse($this->authenticator()->supports(new PasswordCredentials('a@b.c', 'x', 'user')));
    }
}
