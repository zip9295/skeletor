<?php

declare(strict_types=1);

namespace Skeletor\Tests\Core\Security;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Skeletor\Core\Security\EntityRegistry;
use Skeletor\Core\Security\SessionContext;
use Skeletor\Tests\Support\InMemoryAccountRepository;
use Skeletor\Tests\Support\RecordingSessionManager;
use Skeletor\Tests\Support\TestAccount;

/**
 * The read side of a login session.
 *
 * Pinned against the keys LoginService actually writes, so the two cannot drift: a getter
 * reading a key nobody sets returns null, which looks exactly like "not logged in" and would
 * silently log everyone out.
 */
#[CoversClass(SessionContext::class)]
final class SessionContextTest extends TestCase
{
    private RecordingSessionManager $session;

    private EntityRegistry $registry;

    private TestAccount $account;

    protected function setUp(): void
    {
        $this->session = new RecordingSessionManager();
        $this->account = new TestAccount(id: 5, email: 'someone@example.com');
        $this->registry = new EntityRegistry();
        $this->registry->register('user', TestAccount::class, new InMemoryAccountRepository(true, $this->account));
    }

    private function context(): SessionContext
    {
        return new SessionContext($this->session, $this->registry);
    }

    private function signIn(string $type = 'user', int $id = 5): void
    {
        $storage = $this->session->getStorage();
        $storage->offsetSet('loggedIn', $id);
        $storage->offsetSet('loggedInEntityType', $type);
        $storage->offsetSet('loggedInRole', 1);
        $storage->offsetSet('loggedInEmail', 'someone@example.com');
        $storage->offsetSet('loggedInFirstName', 'Test');
        $storage->offsetSet('loggedInLastName', 'Account');
        $storage->offsetSet('redirectPath', '/dashboard/');
    }

    public function testAnEmptySessionIsNobody(): void
    {
        $context = $this->context();

        self::assertFalse($context->isLoggedIn());
        self::assertNull($context->getId());
        self::assertNull($context->getEntityType());
        self::assertNull($context->getUser());
    }

    public function testReadsWhatLoginWrote(): void
    {
        $this->signIn();
        $context = $this->context();

        self::assertTrue($context->isLoggedIn());
        self::assertSame(5, $context->getId());
        self::assertSame('user', $context->getEntityType());
        self::assertSame(1, $context->getRole());
        self::assertSame('someone@example.com', $context->getEmail());
        self::assertSame('Test Account', $context->getDisplayName());
        self::assertSame('/dashboard/', $context->getRedirectPath());
    }

    public function testIdentityIsTheTypeAndTheIdTogether(): void
    {
        // A delegate and a user can both be id 5. Branching on the id alone hands one of them
        // the other's dashboard.
        $this->signIn('delegate', 5);
        $context = $this->context();

        self::assertTrue($context->is('delegate'));
        self::assertFalse($context->is('user'));
    }

    public function testNobodyIsAnythingWhenLoggedOut(): void
    {
        $this->session->getStorage()->offsetSet('loggedInEntityType', 'user');

        self::assertFalse($this->context()->is('user'));
    }

    public function testDisplayNameFallsBackToTheAddress(): void
    {
        // Donors register with an address and nothing else; a blank greeting is worse than
        // an unglamorous one.
        $this->signIn();
        $this->session->getStorage()->offsetSet('loggedInFirstName', null);
        $this->session->getStorage()->offsetSet('loggedInLastName', null);

        self::assertSame('someone@example.com', $this->context()->getDisplayName());
    }

    public function testTheEntityIsLoadedLazilyAndOnlyOnce(): void
    {
        $this->signIn();
        $context = $this->context();

        self::assertSame($this->account, $context->getUser());
        self::assertSame($this->account, $context->getUser(), 'a second call must not hit the repository again');
    }

    public function testAnUnregisteredTypeInTheSessionYieldsNoEntityRatherThanAnError(): void
    {
        // Sessions outlive deployments. A type that has since been removed must degrade to
        // "nobody", not to a 500 on every page.
        $this->signIn('educator', 5);

        self::assertNull($this->context()->getUser());
    }

    public function testASessionPointingAtADeletedRecordYieldsNoEntity(): void
    {
        $this->signIn('user', 999);

        self::assertNull($this->context()->getUser());
    }

    public function testRefreshDropsTheCachedEntity(): void
    {
        $this->signIn();
        $context = $this->context();
        $context->getUser();

        $context->refresh();

        self::assertSame($this->account, $context->getUser());
    }
}
