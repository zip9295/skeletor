<?php

declare(strict_types=1);

namespace Skeletor\Tests\Core\Middleware;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Skeletor\Core\Acl\Acl;
use Skeletor\Core\Config\Config;
use Skeletor\Core\Middleware\AuthMiddleware;
use Skeletor\Core\Security\Authorization\AuthorizationService;
use Skeletor\Core\Security\Authorization\PermissionRegistry;
use Skeletor\Core\Security\AuthPolicy;
use Skeletor\Core\Security\EntityRegistry;
use Skeletor\Tests\Support\InMemoryAccountRepository;
use Skeletor\Tests\Support\Policies;
use Skeletor\Tests\Support\RecordingSessionManager;
use Skeletor\Tests\Support\TestAccount;
use Tamtamchik\SimpleFlash\Flash;

/**
 * The gate in front of every non-public path.
 *
 * Two things are load-bearing and were both wrong: which door an unauthenticated visitor is
 * sent to, and what happens when the session names an account that no longer exists. The
 * second arm hardcoded a URL that ignores both adminPath and the configured login page, so a
 * deleted account landed on a 404 and could never log back in.
 */
#[CoversClass(AuthMiddleware::class)]
final class AuthMiddlewareTest extends TestCase
{
    private RecordingSessionManager $session;
    private EntityRegistry $registry;
    private TestAccount $account;

    /** @var array<string, mixed>|null */
    private ?array $sessionBackup = null;

    protected function setUp(): void
    {
        $this->sessionBackup = $_SESSION ?? null;
        $_SESSION = ['flash_messages' => []];

        $this->session = new RecordingSessionManager();
        $this->account = new TestAccount(id: 5);
        $this->registry = new EntityRegistry();
        $this->registry->register('user', TestAccount::class, new InMemoryAccountRepository(true, $this->account));
    }

    protected function tearDown(): void
    {
        if ($this->sessionBackup === null) {
            unset($_SESSION);
        } else {
            $_SESSION = $this->sessionBackup;
        }
    }

    private function middleware(array $config = [], bool $useVoters = false, ?AuthPolicy $policy = null): AuthMiddleware
    {
        $appConfig = new Config($config + ['adminPath' => '', 'baseUrl' => '', 'adminUrl' => '']);

        return new AuthMiddleware(
            $this->session,
            $appConfig,
            new Flash(),
            // Acl::getMessage() indexes by the MSG_* constants, which are 0 and 1.
            new Acl(
                $this->session,
                $appConfig,
                [0 => ['/login/*'], 1 => ['/dashboard/*']],
                [Acl::MSG_NOT_LOGGED_IN => 'Log in first.', Acl::MSG_NO_PERMISSIONS => 'Not allowed: %s'],
            ),
            $this->registry,
            new AuthorizationService(new PermissionRegistry(['routes' => ['/dashboard/' => [null]]]), new NullLogger()),
            $policy ?? Policies::legacyDefault(),
            $useVoters,
        );
    }

    private function dispatch(AuthMiddleware $middleware, string $path): array
    {
        $reached = false;
        $response = $middleware(
            new ServerRequest('GET', $path),
            new Response(),
            function ($request, $response) use (&$reached) {
                $reached = true;

                return $response;
            }
        );

        return [$response, $reached];
    }

    public function testAGuestPathIsLetThroughUntouched(): void
    {
        [, $reached] = $this->dispatch($this->middleware(), '/login/user/loginForm/');

        self::assertTrue($reached);
    }

    public function testAnAnonymousVisitorIsSentToTheDefaultLoginPage(): void
    {
        [$response, $reached] = $this->dispatch($this->middleware(), '/dashboard/');

        self::assertFalse($reached);
        self::assertSame(302, $response->getStatusCode());
        self::assertStringEndsWith('/login/user/loginForm/', $response->getHeaderLine('Location'));
    }

    public function testTheLoginPageFollowsTheApplicationsDefaultMethod(): void
    {
        // Derived, not configured. The old loginUrl setting was a second list to keep in step
        // with the enabled methods, and it could point at a form the app no longer serves.
        $magicLinkOnly = Policies::with([AuthPolicy::METHOD_MAGIC_LINK], AuthPolicy::METHOD_MAGIC_LINK);

        [$response] = $this->dispatch($this->middleware(policy: $magicLinkOnly), '/dashboard/');

        self::assertStringEndsWith('/login/user/magicLinkForm/', $response->getHeaderLine('Location'));
    }

    public function testTheLoginPageHonoursAnAdminPath(): void
    {
        [$response] = $this->dispatch($this->middleware(['adminPath' => 'secret']), '/dashboard/');

        self::assertStringEndsWith('/secret/login/user/loginForm/', $response->getHeaderLine('Location'));
    }

    public function testAKnownAccountReachesTheApplication(): void
    {
        $this->session->getStorage()->offsetSet('loggedIn', 5);
        $this->session->getStorage()->offsetSet('loggedInEntityType', 'user');

        [, $reached] = $this->dispatch($this->middleware(), '/dashboard/');

        self::assertTrue($reached);
    }

    public function testASessionPointingAtSomethingUnloadableIsClearedRatherThanFollowed(): void
    {
        // Sessions outlive the rows they name. Carrying on with a null entity would put a
        // null through the ACL check, which is a fatal on a page that should have been a
        // redirect back to the login form.
        $this->session->getStorage()->offsetSet('loggedIn', 5);
        $this->session->getStorage()->offsetSet('loggedInEntityType', 'educator');

        [$response, $reached] = $this->dispatch($this->middleware(), '/dashboard/');

        self::assertFalse($reached);
        self::assertNull($this->session->getStorage()->offsetGet('loggedIn'));
        self::assertSame(302, $response->getStatusCode());
    }

    public function testTheStaleSessionRedirectAlsoHonoursTheConfiguredDoor(): void
    {
        // It used to build "/login/loginForm/" by hand, ignoring both adminPath and the
        // configured login page — a 404 for anyone whose account had gone.
        $this->session->getStorage()->offsetSet('loggedIn', 5);
        $this->session->getStorage()->offsetSet('loggedInEntityType', 'educator');

        [$response] = $this->dispatch(
            $this->middleware(
                ['adminPath' => 'secret'],
                policy: Policies::with([AuthPolicy::METHOD_MAGIC_LINK], AuthPolicy::METHOD_MAGIC_LINK)
            ),
            '/dashboard/'
        );

        self::assertStringEndsWith('/secret/login/user/magicLinkForm/', $response->getHeaderLine('Location'));
    }

    public function testEachKindOfAccountIsSentToItsOwnDoor(): void
    {
        // A delegate bounced to the staff login form gets a page they can never get past. The
        // entity type only picks the route segment; which form it lands on is the application's
        // default method.
        //
        // Registered, but with nothing in it -- so getById() returns null rather than throwing.
        // That is the deleted-account case, and it has to reach the same place as an unknown
        // type. The registration matters: the middleware keeps the entity type only while it
        // still resolves, so without this the door would fall back to the default type.
        $this->registry->register('delegate', TestAccount::class, new InMemoryAccountRepository(true));
        $this->session->getStorage()->offsetSet('loggedIn', 5);
        $this->session->getStorage()->offsetSet('loggedInEntityType', 'delegate');
        $magicLinkOnly = Policies::with([AuthPolicy::METHOD_MAGIC_LINK], AuthPolicy::METHOD_MAGIC_LINK);

        [$response] = $this->dispatch($this->middleware(policy: $magicLinkOnly), '/dashboard/');

        self::assertStringEndsWith('/login/delegate/magicLinkForm/', $response->getHeaderLine('Location'));
    }
}
