<?php

declare(strict_types=1);

namespace Skeletor\Tests\Core\Login\Controller;

use GuzzleHttp\Psr7\ServerRequest;
use League\Plates\Engine;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Skeletor\Core\Config\Config;
use Skeletor\Core\Security\Authentication\PendingAuthentication;
use Skeletor\Core\Security\Authenticator\AuthenticatorRegistry;
use Skeletor\Core\Security\Authenticator\MagicLinkAuthenticator;
use Skeletor\Core\Security\Authenticator\PasswordAuthenticator;
use Skeletor\Core\Login\Exception\MethodNotEnabled;
use Skeletor\Core\Login\Repository\LoginRepositoryInterface;
use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Core\Security\AuthPolicy;
use Skeletor\Core\Security\Csrf;
use Skeletor\Core\Security\EntityRegistry;
use Skeletor\Core\Security\TwoFactor\SecretCipher;
use Skeletor\Core\Security\TwoFactor\TotpGenerator;
use Skeletor\Core\Security\TwoFactor\TwoFactorService;
use Skeletor\Core\Login\Controller\LoginController;
use Skeletor\Core\Login\Filter\ForgotPassword as ForgotPasswordFilter;
use Skeletor\Core\Login\Filter\ResetPassword;
use Skeletor\Core\Login\Service\Login;
use Skeletor\Core\Login\Service\MagicLinkService;
use Skeletor\Core\Login\Service\TokenGenerator;
use Skeletor\Core\Login\Validator\ForgotPassword as ForgotPasswordValidator;
use Skeletor\Core\Login\Validator\ResetPasswordLoose;
use Skeletor\Tests\Support\InMemoryAccountRepository;
use Skeletor\Tests\Support\InMemoryForgotPasswordRepository;
use Skeletor\Tests\Support\InMemoryMagicLinkTokenRepository;
use Skeletor\Tests\Support\InMemoryTwoFactorSecretRepository;
use Skeletor\Tests\Support\Policies;
use Skeletor\Tests\Support\RecordingLogger;
use Skeletor\Tests\Support\RecordingMailer;
use Skeletor\Tests\Support\RecordingSessionManager;
use Skeletor\Tests\Support\TestAccount;
use Skeletor\Tests\Support\TranslatedLoginController;
use Skeletor\User\Filter\Login as LoginFilter;
use Skeletor\User\Validator\Login as LoginValidator;
use Tamtamchik\SimpleFlash\Flash;

/**
 * The door itself, driven end to end with real collaborators.
 *
 * Only the storage is faked. The authenticators, the login service, the CSRF service and the
 * two-factor policy are the real classes, so "is there a session afterwards" is answered by
 * the keys that actually get written rather than by a recorded method call — which is the
 * only way this test can catch the failures that matter, all of which are a session existing
 * when it should not.
 */
#[CoversClass(LoginController::class)]
final class LoginControllerTest extends TestCase
{
    private const PASSWORD = 'correct horse battery';
    private const KEY = 'two-factor-encryption-key-for-tests';

    private RecordingSessionManager $session;
    private EntityRegistry $registry;
    private InMemoryMagicLinkTokenRepository $magicTokens;
    private InMemoryTwoFactorSecretRepository $twoFactorSecrets;
    private InMemoryForgotPasswordRepository $resetTokens;
    private RecordingMailer $mailer;
    private RecordingLogger $logger;
    private TestAccount $account;
    private TestAccount $delegate;
    private PendingAuthentication $pending;
    private TwoFactorService $twoFactor;
    private MagicLinkService $magicLinks;
    private \DateTime $now;

    /** @var array<string, mixed>|null */
    private ?array $sessionBackup = null;

    protected function setUp(): void
    {
        $this->sessionBackup = $_SESSION ?? null;
        $_SESSION = ['flash_messages' => []];

        $this->now = new \DateTime('@1111111111');
        $this->session = new RecordingSessionManager();
        $this->account = TestAccount::withPassword(self::PASSWORD, 5, 'someone@example.com');
        $this->delegate = new TestAccount(id: 5, email: 'delegate@example.com');

        $this->registry = new EntityRegistry();
        $this->registry->register('user', TestAccount::class, new InMemoryAccountRepository(true, $this->account));
        $this->registry->register('delegate', TestAccount::class, new InMemoryAccountRepository(true, $this->delegate));

        // Real clock, deliberately. $this->now is pinned to 2005 so the TOTP codes are
        // deterministic, but MagicLinkToken::isExpired() compares against the actual now --
        // so a token minted on that clock is born fifteen minutes expired.
        $this->magicTokens = new InMemoryMagicLinkTokenRepository();
        $this->twoFactorSecrets = new InMemoryTwoFactorSecretRepository($this->now);
        $this->resetTokens = new InMemoryForgotPasswordRepository($this->now);
        $this->mailer = new RecordingMailer();
        $this->logger = new RecordingLogger();
        $this->pending = new PendingAuthentication($this->session);
        // Built here rather than in controller() so a test can issue a link before it builds
        // the controller that follows it.
        $this->magicLinks = new MagicLinkService(
            new TokenGenerator(),
            $this->magicTokens,
            $this->registry,
            $this->mailer,
            new Config(['adminUrl' => 'https://admin.example.com', 'adminPath' => '']),
        );

        $this->twoFactor = new TwoFactorService(
            $this->twoFactorSecrets,
            new TotpGenerator(),
            new SecretCipher(self::KEY),
            $this->now,
            Policies::with([AuthPolicy::METHOD_PASSWORD], AuthPolicy::METHOD_PASSWORD, true),
            'Skeletor',
            maxAttempts: 3,
        );
    }

    protected function tearDown(): void
    {
        if ($this->sessionBackup === null) {
            unset($_SESSION);
        } else {
            $_SESSION = $this->sessionBackup;
        }
    }

    // ---- wiring ---------------------------------------------------------------------

    /**
     * @param class-string<LoginController> $class the concrete controller to build; apps
     *        subclass this controller to translate its messages, and two bugs have already
     *        hidden in the difference between self:: and static:: on that path
     */
    private function controller(
        bool $withTwoFactor = false,
        array $config = [],
        ?AuthPolicy $policy = null,
        string $class = LoginController::class,
    ): LoginController {
        // Password + magic link, which is what these tests exercise. SSO is left out because
        // the registry refuses to be built with an enabled method it has no authenticator for.
        $policy ??= Policies::with(
            [AuthPolicy::METHOD_PASSWORD, AuthPolicy::METHOD_MAGIC_LINK],
            AuthPolicy::METHOD_PASSWORD,
            $withTwoFactor,
        );
        $csrf = new Csrf($this->session);
        $appConfig = new Config($config + [
            'adminPath' => '',
            'adminUrl' => 'https://admin.example.com',
            'captcha' => ['siteKey' => ''],
        ]);
        return new $class(
            new Login(null, $this->session, $this->mailer, $this->resetTokens, null, $this->registry),
            $this->session,
            $appConfig,
            new Flash(),
            $this->templateEngine(),
            $this->logger,
            new ForgotPasswordFilter(new ForgotPasswordValidator($this->resetTokens, $csrf)),
            new LoginFilter(new LoginValidator($csrf)),
            new ResetPassword(new ResetPasswordLoose($this->resetTokens, $csrf)),
            $this->resetTokens,
            $this->magicLinks,
            new AuthenticatorRegistry(
                $policy,
                new PasswordAuthenticator($this->registry, $policy),
                new MagicLinkAuthenticator($this->registry, $policy, $this->magicTokens),
            ),
            $this->registry,
            $policy,
            $this->pending,
            $withTwoFactor ? $this->twoFactor : null,
        );
    }

    /**
     * Plates pointed at the framework's own admin theme, so the pages under test are the
     * shipped templates rather than stand-ins — which makes a template reading a data key the
     * controller does not pass a failing test instead of a blank page in production.
     */
    private function templateEngine(): Engine
    {
        // APP_PATH rather than dirname(__DIR__, n): the arithmetic silently went one level
        // short when this file moved into tests/Core/, and Plates reports a missing theme
        // directory as a LogicException from inside the render, not as a wrong path here.
        $themes = APP_PATH . '/themes/admin';
        $engine = new Engine($themes);
        $engine->addFolder('layout', $themes . '/layout');
        $engine->addFolder('defaultTheme', $themes);
        $engine->registerFunction('formToken', fn (): string => (new Csrf($this->session))->getHiddenInputString());
        $engine->registerFunction('t', fn (string $text): string => $text);
        $engine->registerFunction('getVersionPathPrefix', fn (): string => '');

        return $engine;
    }

    // ---- driving ---------------------------------------------------------------------

    /** A body carrying a token the CSRF service will accept. */
    private function signed(array $body): array
    {
        return $body + (new Csrf($this->session))->getTokenAsArray();
    }

    private function drive(
        LoginController $controller,
        string $method,
        array $body = [],
        array $attributes = ['entityType' => 'user'],
    ): LoginController {
        $request = new ServerRequest($method, '/login/');
        foreach ($attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }
        $controller->setRequest($body ? $request->withParsedBody($body) : $request);

        return $controller;
    }

    /** @return string[] */
    private function flash(string $type): array
    {
        return $_SESSION['flash_messages'][$type] ?? [];
    }

    private function assertNoSession(string $because = 'no session may exist'): void
    {
        self::assertNull($this->session->getStorage()->offsetGet('loggedIn'), $because);
        self::assertNull($this->session->getStorage()->offsetGet('loggedInEntityType'), $because);
    }

    private function assertLoggedInAs(int $id, string $entityType): void
    {
        self::assertSame($id, $this->session->getStorage()->offsetGet('loggedIn'));
        self::assertSame($entityType, $this->session->getStorage()->offsetGet('loggedInEntityType'));
    }

    private function assertRedirectsTo(string $path, ResponseInterface $response): void
    {
        self::assertSame(302, $response->getStatusCode());
        self::assertStringEndsWith($path, $response->getHeaderLine('Location'));
    }

    // ---- password login --------------------------------------------------------------

    public function testTheRightPasswordEstablishesASession(): void
    {
        $controller = $this->drive($this->controller(), 'POST', $this->signed([
            'email' => 'someone@example.com',
            'password' => self::PASSWORD,
        ]));

        $response = $controller->login();

        $this->assertLoggedInAs(5, 'user');
        $this->assertRedirectsTo('/dashboard/', $response);
    }

    public function testTheWrongPasswordEstablishesNothing(): void
    {
        $controller = $this->drive($this->controller(), 'POST', $this->signed([
            'email' => 'someone@example.com',
            'password' => 'not the password',
        ]));

        $response = $controller->login();

        $this->assertNoSession();
        self::assertContains(LoginController::LOGIN_ERROR_INVALID, $this->flash('error'));
        $this->assertRedirectsTo('/login/user/loginForm/', $response);
    }

    public function testAnUnknownAddressIsReportedAsSuch(): void
    {
        $controller = $this->drive($this->controller(), 'POST', $this->signed([
            'email' => 'nobody@example.com',
            'password' => self::PASSWORD,
        ]));

        $controller->login();

        $this->assertNoSession();
        self::assertContains(LoginController::LOGIN_ERROR_NO_EMAIL, $this->flash('error'));
    }

    public function testAPostWithoutAValidFormTokenIsRefused(): void
    {
        $controller = $this->drive($this->controller(), 'POST', [
            'email' => 'someone@example.com',
            'password' => self::PASSWORD,
        ]);

        $controller->login();

        $this->assertNoSession();
        self::assertContains(LoginController::LOGIN_ERROR_TOKEN, $this->flash('error'));
    }

    // ---- what a subclass sees ------------------------------------------------------

    public function testASubclassGetsItsOwnMessageFromTheFailureTable(): void
    {
        $controller = $this->drive($this->controller(class: TranslatedLoginController::class), 'POST', [
            'email' => 'someone@example.com',
            'password' => self::PASSWORD,
        ]);

        $controller->login();

        // Not merely "some error was flashed": the framework's English must be absent, or a
        // table that ignored the override would still pass the positive half of this.
        self::assertContains(TranslatedLoginController::TRANSLATED_TOKEN, $this->flash('error'));
        self::assertNotContains(LoginController::LOGIN_ERROR_TOKEN, $this->flash('error'));
    }

    public function testASubclassInheritsTheMessagesItDidNotRedeclare(): void
    {
        $controller = $this->drive($this->controller(class: TranslatedLoginController::class), 'POST', $this->signed([
            'email' => 'nobody@example.com',
            'password' => self::PASSWORD,
        ]));

        $controller->login();

        self::assertContains(LoginController::LOGIN_ERROR_NO_EMAIL, $this->flash('error'));
    }

    public function testRememberMeIsPassedThroughToTheSession(): void
    {
        $controller = $this->drive($this->controller(), 'POST', $this->signed([
            'email' => 'someone@example.com',
            'password' => self::PASSWORD,
            'rememberMe' => 'on',
        ]));

        $controller->login();

        self::assertTrue($this->session->remembered);
    }

    public function testADeactivatedAccountCannotLogIn(): void
    {
        $this->account->deactivate();
        $controller = $this->drive($this->controller(), 'POST', $this->signed([
            'email' => 'someone@example.com',
            'password' => self::PASSWORD,
        ]));

        $controller->login();

        $this->assertNoSession();
    }

    // ---- which kind of account -------------------------------------------------------

    public function testTheEntityTypeComesFromTheRouteNotTheBody(): void
    {
        // Both accounts are id 5 on purpose. If the posted body could name the type, editing
        // a hidden field on the delegate form would be a way to ask for a staff session.
        $controller = $this->drive(
            $this->controller(),
            'POST',
            $this->signed([
                'email' => 'someone@example.com',
                'password' => self::PASSWORD,
                'entityType' => 'delegate',
            ]),
            ['entityType' => 'user'],
        );

        $controller->login();

        $this->assertLoggedInAs(5, 'user');
    }

    public function testAnUnregisteredEntityTypeInTheUrlIsA404(): void
    {
        // Falling back to "user" here would answer a probe for which account types exist by
        // logging the prober in. It is a 404 for the same reason a disabled method is: the
        // endpoint genuinely does not exist.
        $controller = $this->drive(
            $this->controller(),
            'POST',
            $this->signed(['email' => 'someone@example.com', 'password' => self::PASSWORD]),
            ['entityType' => 'educator'],
        );

        try {
            $controller->login();
            self::fail('an unregistered entity type should not reach the authenticator');
        } catch (MethodNotEnabled) {
            $this->assertNoSession();
        }
    }

    // ---- logging out -----------------------------------------------------------------

    public function testLoggingOutEndsTheSessionAndReturnsToTheRightDoor(): void
    {
        $storage = $this->session->getStorage();
        $storage->offsetSet('loggedIn', 5);
        $storage->offsetSet('loggedInEntityType', 'delegate');

        $response = $this->drive($this->controller(), 'GET')->logOut();

        $this->assertNoSession();
        self::assertTrue($this->session->destroyed);
        $this->assertRedirectsTo('/login/delegate/loginForm/', $response);
    }


    // ---- the second factor -----------------------------------------------------------

    private function enrolAccount(): string
    {
        $enrolment = $this->twoFactor->beginEnrolment('user', $this->account);
        $this->twoFactor->confirmEnrolment('user', 5, (new TotpGenerator())->at($enrolment->secret, $this->now->getTimestamp()));

        return $enrolment->secret;
    }

    private function passwordLogin(LoginController $controller): ResponseInterface
    {
        return $this->drive($controller, 'POST', $this->signed([
            'email' => 'someone@example.com',
            'password' => self::PASSWORD,
        ]))->login();
    }

    public function testACorrectPasswordAloneDoesNotLogInAnAccountThatOwesASecondFactor(): void
    {
        // The property the whole pending-authentication machinery exists for.
        $this->enrolAccount();
        $controller = $this->controller(withTwoFactor: true);

        $response = $this->passwordLogin($controller);

        $this->assertNoSession('the password is only the first half');
        self::assertTrue($this->pending->isPending());
        $this->assertRedirectsTo('/login/user/twoFactorForm/', $response);
    }

    public function testAnAccountThatOwesASecondFactorButHasNoneIsSentToSetOneUp(): void
    {
        $controller = $this->controller(withTwoFactor: true);

        $response = $this->passwordLogin($controller);

        $this->assertNoSession();
        $this->assertRedirectsTo('/login/user/twoFactorSetup/', $response);
    }

    public function testAnAccountThatOptsOutIsLetStraightInEvenWithTwoFactorOn(): void
    {
        // The switch is application-wide, so "this entity type is exempt" is no longer a thing
        // config can say -- the account says it, via TwoFactorAwareInterface. A donor on a public
        // site should not be dragged through an authenticator app because the staff dashboard
        // requires one.
        $this->delegate->requireTwoFactor(false);
        $controller = $this->controller(withTwoFactor: true);
        $token = $this->magicLinks->requestMagicLink('delegate@example.com', 'delegate', sendEmail: false);

        $this->drive($controller, 'POST', ['token' => $token], ['entityType' => 'delegate'])->verifyMagicLink();

        $this->assertLoggedInAs(5, 'delegate');
    }

    public function testTheRightCodeCompletesTheLogin(): void
    {
        $secret = $this->enrolAccount();
        $controller = $this->controller(withTwoFactor: true);
        $this->passwordLogin($controller);

        $code = (new TotpGenerator())->at($secret, $this->now->getTimestamp() + 30);
        $response = $this->drive($controller, 'POST', $this->signed(['code' => $code]))->twoFactor();

        $this->assertLoggedInAs(5, 'user');
        $this->assertRedirectsTo('/dashboard/', $response);
    }

    public function testTheChallengeIgnoresAnyAccountNamedInThePostedBody(): void
    {
        // The bug this design exists to make impossible: a two-factor step that takes the
        // account id from the form lets whoever posts the form choose whose code is checked,
        // and therefore whose session is created.
        $secret = $this->enrolAccount();
        $controller = $this->controller(withTwoFactor: true);
        $this->passwordLogin($controller);

        $code = (new TotpGenerator())->at($secret, $this->now->getTimestamp() + 30);
        $this->drive($controller, 'POST', $this->signed([
            'code' => $code,
            'userId' => 999,
            'entityId' => 999,
            'entityType' => 'delegate',
        ]))->twoFactor();

        $this->assertLoggedInAs(5, 'user');
    }

    public function testAWrongCodeEstablishesNoSession(): void
    {
        $this->enrolAccount();
        $controller = $this->controller(withTwoFactor: true);
        $this->passwordLogin($controller);

        $response = $this->drive($controller, 'POST', $this->signed(['code' => '000000']))->twoFactor();

        $this->assertNoSession();
        $this->assertRedirectsTo('/login/user/twoFactorForm/', $response);
    }

    public function testTheChallengeCannotBeAnsweredWithoutHavingPassedTheFirstFactor(): void
    {
        // Otherwise the second factor is reachable on its own, which makes it the only one.
        $secret = $this->enrolAccount();
        $controller = $this->controller(withTwoFactor: true);

        $code = (new TotpGenerator())->at($secret, $this->now->getTimestamp() + 30);
        $response = $this->drive($controller, 'POST', $this->signed(['code' => $code]))->twoFactor();

        $this->assertNoSession();
        $this->assertRedirectsTo('/login/user/loginForm/', $response);
    }

    public function testTheChallengeRequiresItsOwnFormToken(): void
    {
        $secret = $this->enrolAccount();
        $controller = $this->controller(withTwoFactor: true);
        $this->passwordLogin($controller);

        $code = (new TotpGenerator())->at($secret, $this->now->getTimestamp() + 30);
        $this->drive($controller, 'POST', ['code' => $code])->twoFactor();

        $this->assertNoSession();
    }

    public function testEnrollingAndConfirmingLogsTheAccountIn(): void
    {
        $controller = $this->controller(withTwoFactor: true);
        $this->passwordLogin($controller);

        $this->drive($controller, 'GET')->twoFactorSetup();
        $secret = $this->pending->getEnrolmentSecret();
        self::assertNotNull($secret, 'the setup page must hold on to the secret it issued');

        $code = (new TotpGenerator())->at($secret, $this->now->getTimestamp() + 30);
        $this->drive($controller, 'POST', $this->signed(['code' => $code]))->twoFactorSetupConfirm();

        $this->assertLoggedInAs(5, 'user');
        self::assertTrue($this->twoFactor->isEnrolled('user', 5));
    }

    public function testReopeningTheSetupPageShowsTheSameSecret(): void
    {
        // Rotating it on every render would move the QR under a user halfway through
        // scanning it, and no code they entered would ever work.
        $controller = $this->controller(withTwoFactor: true);
        $this->passwordLogin($controller);

        $this->drive($controller, 'GET')->twoFactorSetup();
        $first = $this->pending->getEnrolmentSecret();
        $this->drive($controller, 'GET')->twoFactorSetup();

        self::assertSame($first, $this->pending->getEnrolmentSecret());
    }

    public function testAnAbandonedChallengeCannotBeResumedAfterItExpires(): void
    {
        $secret = $this->enrolAccount();
        $controller = $this->controller(withTwoFactor: true);
        $this->passwordLogin($controller);

        // Reach in and expire the pending state the way the clock would.
        $state = $this->session->getStorage()->offsetGet(PendingAuthentication::SESSION_KEY);
        $state['expiresAt'] = time() - 1;
        $this->session->getStorage()->offsetSet(PendingAuthentication::SESSION_KEY, $state);

        $code = (new TotpGenerator())->at($secret, $this->now->getTimestamp() + 30);
        $this->drive($controller, 'POST', $this->signed(['code' => $code]))->twoFactor();

        $this->assertNoSession();
    }

    // ---- magic links -----------------------------------------------------------------

    public function testRequestingALinkIssuesOneAndSaysSo(): void
    {
        $controller = $this->controller();

        $response = $this->drive($controller, 'POST', $this->signed(['email' => 'someone@example.com']))
            ->requestMagicLink();

        self::assertCount(1, $this->mailer->magicLinks);
        self::assertContains(LoginController::MAGIC_LINK_SENT, $this->flash('success'));
        $this->assertRedirectsTo('/login/user/magicLinkForm/?sent', $response);
    }

    public function testAnInactiveAccountIsRefusedBeforeAnyLinkIsIssued(): void
    {
        // The check one app had in its own login controller and the framework did not, so it
        // applied at one entry point and not the others.
        $this->account->deactivate();
        $controller = $this->controller();

        $this->drive($controller, 'POST', $this->signed(['email' => 'someone@example.com']))->requestMagicLink();

        self::assertSame([], $this->magicTokens->tokens, 'no token may be minted for an inactive account');
        self::assertSame([], $this->mailer->magicLinks);
    }

    public function testALinkRequestForAnUnknownAddressSendsNothing(): void
    {
        $controller = $this->controller();

        $this->drive($controller, 'POST', $this->signed(['email' => 'nobody@example.com']))->requestMagicLink();

        self::assertSame([], $this->mailer->magicLinks);
        self::assertContains(LoginController::LOGIN_ERROR_NO_EMAIL, $this->flash('error'));
    }

    public function testAMalformedAddressIsRejectedWithoutLookingAnythingUp(): void
    {
        $controller = $this->controller();

        $this->drive($controller, 'POST', $this->signed(['email' => 'not-an-address']))->requestMagicLink();

        self::assertSame([], $this->magicTokens->tokens);
    }

    public function testALinkIsIssuedForTheEntityTypeInTheRouteNotTheBody(): void
    {
        $controller = $this->controller();

        $this->drive(
            $controller,
            'POST',
            $this->signed(['email' => 'delegate@example.com', 'entityType' => 'user']),
            ['entityType' => 'delegate'],
        )->requestMagicLink();

        self::assertCount(1, $this->magicTokens->issuedFor('delegate', 5));
    }

    public function testFollowingALinkLogsTheAccountIn(): void
    {
        $token = $this->magicLinks->requestMagicLink('someone@example.com', 'user', sendEmail: false);
        $controller = $this->controller();

        $response = $this->drive($controller, 'POST', ['token' => $token])->verifyMagicLink();

        $this->assertLoggedInAs(5, 'user');
        $this->assertRedirectsTo('/dashboard/', $response);
    }

    public function testMerelyFetchingTheLinkDoesNotSpendIt(): void
    {
        // Mobile mail clients and in-app browsers prefetch links to build previews. When a
        // GET consumed the token, that prefetch burned it seconds after it was issued — on
        // phones only, which is why it looked like an intermittent fault for so long.
        $token = $this->magicLinks->requestMagicLink('someone@example.com', 'user', sendEmail: false);
        $controller = $this->controller();

        $this->drive($controller, 'GET', [], ['entityType' => 'user', 'token' => $token])->verifyMagicLink();

        self::assertTrue($this->magicTokens->findByToken($token)->isUsable(), 'a GET must leave the link alive');
        $this->assertNoSession('and must not log anyone in either');
        // This branch renders a real template. respond() catches a failed render, logs it and
        // writes the message into the body, so without this the assertions above would still
        // pass on a page that never rendered.
        self::assertSame([], $this->logger->messages(), 'the confirmation page must render cleanly');
    }

    public function testALinkIsSingleUse(): void
    {
        $token = $this->magicLinks->requestMagicLink('someone@example.com', 'user', sendEmail: false);
        $this->drive($this->controller(), 'POST', ['token' => $token])->verifyMagicLink();

        $secondSession = new RecordingSessionManager();
        $this->session = $secondSession;
        $this->pending = new PendingAuthentication($secondSession);
        $response = $this->drive($this->controller(), 'POST', ['token' => $token])->verifyMagicLink();

        $this->assertNoSession();
        $this->assertRedirectsTo('/login/user/magicLinkForm/', $response);
    }

    public function testAnUnknownTokenLogsNobodyIn(): void
    {
        $response = $this->drive($this->controller(), 'POST', ['token' => str_repeat('f', 128)])->verifyMagicLink();

        $this->assertNoSession();
        $this->assertRedirectsTo('/login/user/magicLinkForm/', $response);
    }

    public function testALinkIssuedForOneEntityTypeDoesNotWorkForAnother(): void
    {
        // Both accounts are id 5. Without the type check on the token, a delegate link would
        // open a staff session.
        $token = $this->magicLinks->requestMagicLink('delegate@example.com', 'delegate', sendEmail: false);

        $this->drive($this->controller(), 'POST', ['token' => $token], ['entityType' => 'user'])->verifyMagicLink();

        $this->assertNoSession();
    }

    public function testRememberMeSurvivesTheRoundTripThroughTheLink(): void
    {
        $token = $this->magicLinks->requestMagicLink('someone@example.com', 'user', sendEmail: false, rememberMe: true);

        $this->drive($this->controller(), 'POST', ['token' => $token])->verifyMagicLink();

        self::assertTrue($this->session->remembered);
    }

    public function testALinkCannotSkipASecondFactor(): void
    {
        // Two-factor hangs off the entity type, not off how they arrived, so choosing a
        // different first factor cannot shed it.
        $this->enrolAccount();
        $token = $this->magicLinks->requestMagicLink('someone@example.com', 'user', sendEmail: false);

        $response = $this->drive($this->controller(withTwoFactor: true), 'POST', ['token' => $token])
            ->verifyMagicLink();

        $this->assertNoSession();
        $this->assertRedirectsTo('/login/user/twoFactorForm/', $response);
    }

    public function testARequestWithNoTokenNeverReachesTheAuthenticator(): void
    {
        $response = $this->drive($this->controller(), 'GET')->verifyMagicLink();

        $this->assertNoSession();
        $this->assertRedirectsTo('/login/user/magicLinkForm/', $response);
    }

    // ---- what this application actually offers ---------------------------------------

    /** @return list<array{string, string}> action and the method that has to be on for it */
    public static function guardedActions(): array
    {
        return [
            'password form'      => ['loginForm', AuthPolicy::METHOD_PASSWORD],
            'password submit'    => ['login', AuthPolicy::METHOD_PASSWORD],
            'forgot form'        => ['forgotPasswordForm', AuthPolicy::METHOD_PASSWORD],
            'forgot submit'      => ['forgotPassword', AuthPolicy::METHOD_PASSWORD],
            'reset form'         => ['resetPasswordForm', AuthPolicy::METHOD_PASSWORD],
            'reset submit'       => ['resetPassword', AuthPolicy::METHOD_PASSWORD],
            'magic link form'    => ['magicLinkForm', AuthPolicy::METHOD_MAGIC_LINK],
            'magic link request' => ['requestMagicLink', AuthPolicy::METHOD_MAGIC_LINK],
            'magic link verify'  => ['verifyMagicLink', AuthPolicy::METHOD_MAGIC_LINK],
            'sso start'          => ['sso', AuthPolicy::METHOD_SSO],
            'sso callback'       => ['ssoCallback', AuthPolicy::METHOD_SSO],
        ];
    }

    #[DataProvider('guardedActions')]
    public function testADisabledMethodHasNoEndpoints(string $action, string $method): void
    {
        // 404, not a redirect or a validation error: an endpoint for a method this app does
        // not offer should not exist, and answering anything else confirms it is merely off.
        $enabled = array_values(array_diff(
            [AuthPolicy::METHOD_PASSWORD, AuthPolicy::METHOD_MAGIC_LINK],
            [$method]
        ));
        $policy = Policies::with($enabled ?: [AuthPolicy::METHOD_MAGIC_LINK], $enabled[0] ?? AuthPolicy::METHOD_MAGIC_LINK);
        $controller = $this->drive($this->controller(policy: $policy), 'GET');

        $this->expectException(NotFoundException::class);
        $controller->{$action}();
    }

    public function testTwoFactorEndpointsDoNotExistWhenTheApplicationHasItOff(): void
    {
        $controller = $this->drive($this->controller(withTwoFactor: false), 'GET');

        $this->expectException(NotFoundException::class);
        $controller->twoFactorForm();
    }

    public function testAnEnabledMethodStillWorksWhenAnotherIsOff(): void
    {
        // The switch has to be a switch, not a kill: turning passwords off must leave the
        // magic-link flow untouched.
        $policy = Policies::with([AuthPolicy::METHOD_MAGIC_LINK], AuthPolicy::METHOD_MAGIC_LINK);
        $token = $this->magicLinks->requestMagicLink('someone@example.com', 'user', sendEmail: false);

        $this->drive($this->controller(policy: $policy), 'POST', ['token' => $token])->verifyMagicLink();

        $this->assertLoggedInAs(5, 'user');
    }

    public function testFallbackRedirectsFollowTheApplicationsOwnFrontDoor(): void
    {
        // An app with passwords off has no loginForm to bounce anyone to. Before the policy
        // this redirect was hardcoded and would have 404d. logOut() is the surviving caller of
        // defaultLoginPath() now that an unknown entity type throws instead of redirecting.
        $policy = Policies::with([AuthPolicy::METHOD_MAGIC_LINK], AuthPolicy::METHOD_MAGIC_LINK);
        $this->session->getStorage()->offsetSet('loggedIn', 5);
        $this->session->getStorage()->offsetSet('loggedInEntityType', 'user');

        $response = $this->drive($this->controller(policy: $policy), 'GET')->logOut();

        $this->assertRedirectsTo('/login/user/magicLinkForm/', $response);
    }

    public function testLogoutReturnsToTheApplicationsFrontDoor(): void
    {
        $policy = Policies::with([AuthPolicy::METHOD_MAGIC_LINK], AuthPolicy::METHOD_MAGIC_LINK);
        $this->session->getStorage()->offsetSet('loggedIn', 5);
        $this->session->getStorage()->offsetSet('loggedInEntityType', 'delegate');

        $response = $this->drive($this->controller(policy: $policy), 'GET')->logOut();

        $this->assertNoSession();
        $this->assertRedirectsTo('/login/delegate/magicLinkForm/', $response);
    }

    // ---- already signed in, without looping --------------------------------------------

    private function driveAt(LoginController $controller, string $path): LoginController
    {
        $controller->setRequest(
            (new ServerRequest('GET', $path))->withAttribute('entityType', 'user')
        );

        return $controller;
    }

    public function testAnAlreadyLoggedInVisitorIsNotSentBackToThePageTheyAreOn(): void
    {
        // The ERR_TOO_MANY_REDIRECTS case. The framework User carried redirectPath = "/" while
        // the app routes "/" to the login form, so logging in redirected to the form, which
        // redirected to redirectPath, forever.
        $this->session->getStorage()->offsetSet('loggedIn', 5);
        $this->session->getStorage()->offsetSet('redirectPath', '/login/user/loginForm/');

        $controller = $this->controller(config: ['redirectUri' => '/user/view/']);
        $response = $this->driveAt($controller, '/login/user/loginForm/')->loginForm();

        $this->assertRedirectsTo('/user/view/', $response);
    }

    public function testTrailingSlashesDoNotHideTheCollision(): void
    {
        $this->session->getStorage()->offsetSet('loggedIn', 5);
        $this->session->getStorage()->offsetSet('redirectPath', '/login/user/loginForm');

        $controller = $this->controller(config: ['redirectUri' => '/user/view/']);
        $response = $this->driveAt($controller, '/login/user/loginForm/')->loginForm();

        $this->assertRedirectsTo('/user/view/', $response);
    }

    public function testWhenEveryDestinationIsThisPageTheFormRendersRatherThanLooping(): void
    {
        // Nothing left to redirect to. Showing a logged-in visitor the login form is
        // harmless; bouncing them until the browser gives up is not.
        $this->session->getStorage()->offsetSet('loggedIn', 5);
        $this->session->getStorage()->offsetSet('redirectPath', '/login/user/loginForm/');

        $controller = $this->controller(config: ['redirectUri' => '/login/user/loginForm/']);
        $response = $this->driveAt($controller, '/login/user/loginForm/')->loginForm();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $this->logger->messages(), 'the form itself must still render');
    }

    // ---- how failures are reported ------------------------------------------------------

    public function testAnUnexpectedFailureIsLoggedAndNeverShownToTheVisitor(): void
    {
        // The whole reason fail() has a default arm. Anything not in the failure table is ours,
        // not theirs: a login page must not print a database error, and swallowing it silently
        // is how the original var_dump-in-a-catch survived for so long.
        $this->registry->register('broken', TestAccount::class, new class implements LoginRepositoryInterface {
            public function findByEmail(string $email)
            {
                throw new \RuntimeException('SQLSTATE[HY000] connection refused');
            }

            public function updatePassword($userId, $password) {}

            public function updateLoginInfo($model) {}

            public function getById($id)
            {
                return null;
            }
        });

        $this->drive(
            $this->controller(),
            'POST',
            $this->signed(['email' => 'someone@example.com']),
            ['entityType' => 'broken'],
        )->requestMagicLink();

        self::assertContains(LoginController::GENERIC_ERROR, $this->flash('error'));
        self::assertNotContains('SQLSTATE[HY000] connection refused', $this->flash('error'));
        self::assertSame(['SQLSTATE[HY000] connection refused'], $this->logger->messages());
    }

    public function testAMalformedAddressIsRefusedBeforeAnythingIsLookedUp(): void
    {
        $this->drive(
            $this->controller(),
            'POST',
            $this->signed(['email' => 'not-an-address']),
            ['entityType' => 'user'],
        )->requestMagicLink();

        self::assertContains(LoginController::INVALID_EMAIL, $this->flash('error'));
        self::assertSame([], $this->magicTokens->tokens, 'no token, and no query either');
    }

    public function testADeadResetLinkSaysSoRatherThanReportingAGenericError(): void
    {
        // InvalidResetToken carries a message written for the visitor, so it is one of the few
        // shown as thrown. Everything else in that catch collapses to GENERIC_ERROR, and this
        // is the test that stops the useful message collapsing with it.
        $response = $this->drive(
            $this->controller(),
            'POST',
            $this->signed(['password' => 'a-new-one', 'password2' => 'a-new-one']),
            ['entityType' => 'user', 'token' => '$5$no-such-token'],
        )->resetPassword();

        self::assertNotContains(LoginController::GENERIC_ERROR, $this->flash('error'));
        self::assertNotSame([], $this->flash('error'), 'the visitor is told the link is dead');
        self::assertSame(302, $response->getStatusCode());
    }
}
