<?php

declare(strict_types=1);

namespace Skeletor\Core\Login\Controller;

use Laminas\Session\ManagerInterface;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface;
use Skeletor\Core\Config\Config;
use Skeletor\Core\Controller\Controller;
use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Core\Security\Authentication\AuthenticatableInterface;
use Skeletor\Core\Security\Authentication\MagicLinkCredentials;
use Skeletor\Core\Security\Authentication\PasswordCredentials;
use Skeletor\Core\Security\Authentication\PendingAuthentication;
use Skeletor\Core\Security\Authentication\SsoCredentials;
use Skeletor\Core\Security\Authenticator\AuthenticatorRegistry;
use Skeletor\Core\Security\AuthPolicy;
use Skeletor\Core\Security\EntityRegistry;
use Skeletor\Core\Security\TwoFactor\TwoFactorService;
use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Login\Exception\InvalidCredentials;
use Skeletor\Core\Login\Exception\InvalidEmailAddress;
use Skeletor\Core\Login\Exception\InvalidResetToken;
use Skeletor\Core\Login\Exception\MethodNotEnabled;
use Skeletor\Core\Login\Exception\MagicLinkThrottled;
use Skeletor\Core\Login\Filter\ForgotPassword as ForgotPasswordFilter;
use Skeletor\Core\Login\Filter\ResetPassword;
use Skeletor\Core\Login\Repository\ForgotPasswordRepository;
use Skeletor\Core\Login\Service\Login;
use Skeletor\Core\Login\Service\MagicLinkService;
use Skeletor\User\Filter\Login as Filter;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

/**
 * The front door, for every kind of account.
 *
 * One controller serves all registered entity types: the type comes from the route
 * ({entityType} in /login/{entityType}/{action}/), is checked against the entity registry,
 * and is carried through every redirect. Apps used to fork this class per entity type to get
 * that, and the forks drifted — one grew an is-this-account-active check that the other
 * never had, which is the kind of difference nobody notices until it is a way in.
 *
 * Three ways in, all landing in completeLogin(): a password, a magic link, and an OAuth
 * callback. Two-factor sits after whichever one was used, not inside it, so a second factor
 * cannot be skipped by choosing a different first one.
 */
class LoginController extends Controller
{
    const LOGGED_OUT = 'You have successfully logged out.';

    const LOGIN_ERROR_INVALID = 'Invalid credentials provided.';
    const LOGIN_ERROR_NO_EMAIL = 'Email not found in system.';
    const LOGIN_ERROR_INACTIVE = 'This account is not active.';
    const LOGIN_SUCCESS = 'You have successfully logged in.';
    const LOGIN_ERROR_TOKEN = 'Invalid form token. Please refresh the page and try again.';
    const MAGIC_LINK_SENT = 'Magic link sent! Please check your email.';
    const MAGIC_LINK_INVALID = 'Invalid or expired login link.';
    const MAGIC_LINK_THROTTLED = 'A login link was just sent. Please check your inbox.';
    const INVALID_EMAIL = 'Please enter a valid email address';
    const GENERIC_ERROR = 'An error occurred. Please try again.';
    const TWO_FACTOR_ENABLED = 'Two-factor authentication is now active on your account.';

    const DEFAULT_ENTITY_TYPE = 'user';

    /**
     * What to say when a login flow fails, by exception.
     *
     * Every action used to spell this out in four to six catch arms of its own, which was a
     * quarter of the file saying the same three things. An action now states only where it
     * differs from this table -- see fail().
     *
     * A method rather than a constant, for one reason: the messages are constants an app
     * redeclares in its own subclass to translate them, and a constant array can only reach
     * them through self::, which binds to this class. The table would then hold this class's
     * English however the subclass had been written -- silently, since nothing about it looks
     * wrong. static:: is available in a method body and not in a constant expression, so the
     * table has to be one. Override it to add rows; call parent::failures() to keep these.
     *
     * @return array<class-string, string|null>
     */
    protected static function failures(): array
    {
        return [
            InvalidFormTokenException::class => static::LOGIN_ERROR_TOKEN,
            InvalidEmailAddress::class       => static::INVALID_EMAIL,
            MagicLinkThrottled::class        => static::MAGIC_LINK_THROTTLED,
            NotFoundException::class         => static::LOGIN_ERROR_NO_EMAIL,
            InvalidCredentials::class        => static::LOGIN_ERROR_INVALID,
        ];
    }

    // Redeclared only to change the default. Property types are invariant, so this has to
    // stay `string` to match Controller — and note the parent constructor overwrites it
    // from config.adminPath immediately, so the '' never survives construction.
    public string $protectedPath = '';

    public function __construct(
        public readonly Login $loginService,
        ManagerInterface $session,
        Config $config,
        Flash $flash,
        Engine $template,
        Logger $logger,
        private ForgotPasswordFilter $forgotPasswordFilter,
        private Filter $loginFilter,
        protected ResetPassword $resetPasswordFilter,
        protected ForgotPasswordRepository $forgotPasswordRepository,
        private MagicLinkService $magicLinkService,
        private AuthenticatorRegistry $authenticatorRegistry,
        private EntityRegistry $entityRegistry,
        private AuthPolicy $policy,
        private PendingAuthentication $pendingAuthentication,
        private ?TwoFactorService $twoFactorService = null,
    ) {
        parent::__construct($template, $config, $session, $flash, $logger);
    }

    /**
     * The entity type this request is about.
     *
     * Never defaults a *present* segment: silently reading /login/nonsense/ as a user login
     * would answer a probe for which account types exist by logging someone in. An
     * unregistered type is a 404 for the same reason a disabled method is -- the endpoint
     * genuinely does not exist -- which is also why this throws rather than returning null and
     * making every caller write the same guard.
     *
     * @throws MethodNotEnabled
     */
    private function entityType(): string
    {
        $type = $this->getRequest()->getAttribute('entityType');
        if ($type === null || $type === '') {
            return static::DEFAULT_ENTITY_TYPE;
        }

        if (!$this->entityRegistry->has($type)) {
            throw new MethodNotEnabled(sprintf('No login for entity type "%s".', $type));
        }

        return $type;
    }

    /**
     * A path within this controller, for the given (or current) entity type.
     *
     * The literal "admin/" is what Controller::redirect() swaps for the configured adminPath,
     * so it has to be present even though it is never what the visitor sees.
     */
    private function path(string $action, ?string $entityType = null): string
    {
        return sprintf('/admin/login/%s/%s/', $entityType ?? static::DEFAULT_ENTITY_TYPE, $action);
    }

    /**
     * The front door for this application, whatever it happens to be.
     *
     * Used wherever a flow needs to send someone back to "the login page" without knowing
     * which one that is -- an abandoned two-factor challenge, a logout, an unrecognised entity
     * type. Redirecting to loginForm unconditionally would 404 in an app that has passwords
     * switched off.
     */
    private function defaultLoginPath(?string $entityType = null): string
    {
        return '/admin' . $this->policy->loginPath($entityType ?? static::DEFAULT_ENTITY_TYPE);
    }

    /**
     * Two-factor endpoints do not exist in an application that has not switched it on.
     *
     * @throws MethodNotEnabled
     */
    private function assertTwoFactorEnabled(): void
    {
        if (!$this->twoFactorService || !$this->policy->twoFactorRequired()) {
            throw new MethodNotEnabled('Two-factor authentication is not enabled.');
        }
    }

    /**
     * Turn a failed login step into a message and a destination.
     *
     * $messages overrides the failures() table where an action means something different by the
     * same exception -- InvalidCredentials is "wrong password" on the password form, "your
     * account is not active" on the magic-link form, and "this link is dead" on the verify
     * leg. A null message means show the exception's own text, which is right only where that
     * text was written for the visitor (the two-factor service) rather than for a log.
     *
     * @param array<class-string, string|null> $messages
     */
    private function fail(
        \Throwable $e,
        string $backTo,
        array $messages = [],
        ?FilterInterface $filter = null
    ): ResponseInterface {
        // A 404 is not a login failure. Rethrow before anything can match it against
        // NotFoundException below and turn "this endpoint does not exist" into "no such email".
        if ($e instanceof MethodNotEnabled) {
            throw $e;
        }

        if ($e instanceof ValidatorException && $filter) {
            foreach ($filter->getErrors() as $group) {
                foreach ((array) $group as $message) {
                    $this->getFlash()->error($this->translate($message));
                }
            }

            return $this->redirect($backTo);
        }

        foreach ($messages + static::failures() as $class => $message) {
            if ($e instanceof $class) {
                $this->getFlash()->error($this->translate($message ?? $e->getMessage()));

                return $this->redirect($backTo);
            }
        }

        // Anything else is ours, not theirs: logged in full, and reported as a generic error.
        // A public page must never print a database or mapping failure.
        $this->logger->error($e->getMessage());
        $this->getFlash()->error($this->translate(static::GENERIC_ERROR));

        return $this->redirect($backTo);
    }

    /** @throws InvalidFormTokenException */
    private function requireCsrf(array $params): void
    {
        if (!$this->csrf()->validate($params)) {
            throw new InvalidFormTokenException();
        }
    }

    /** @throws InvalidEmailAddress */
    private function requireEmail(array $params): string
    {
        $email = trim((string) ($params['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailAddress();
        }

        return $email;
    }

    private function isPost(): bool
    {
        return strtoupper($this->getRequest()->getMethod()) === 'POST';
    }

    /**
     * Where an already-logged-in visitor should be sent instead of a login form.
     *
     * Returns null when there is nowhere sensible to send them, and the caller then renders
     * the form rather than redirecting. That case is not hypothetical: an entity whose
     * redirectPath is "/" in an app that routes "/" to the login form produces a redirect
     * straight back to here, and the browser gives up with ERR_TOO_MANY_REDIRECTS. Skipping
     * any candidate that is the page we are already on makes that structurally impossible,
     * whatever an app configures.
     */
    private function alreadyLoggedInRedirect(): ?ResponseInterface
    {
        $current = '/' . trim($this->getRequest()->getUri()->getPath(), '/');

        // No bare '/' fallback. It looks harmless -- '/' is not the page we are on -- but in an
        // app that routes '/' to the login form it just makes the loop two steps long instead of
        // one: this page sends them to '/', '/' renders the form, redirectPath no longer collides
        // with '/', and back they come. Rendering the form is the only safe answer when every
        // candidate is this page.
        $candidates = [
            $this->getSession()->getStorage()->offsetGet('redirectPath'),
            $this->getConfig()->offsetGet('redirectUri'),
        ];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || $candidate === '') {
                continue;
            }
            if ('/' . trim($candidate, '/') === $current) {
                continue;
            }

            return $this->redirect($candidate);
        }

        return null;
    }

    private function isLoggedIn(): bool
    {
        return (bool) $this->getSession()->getStorage()->offsetGet('loggedIn');
    }

    // ---- password ------------------------------------------------------------------

    public function loginForm(): ResponseInterface
    {
        $this->policy->assertEnabled(AuthPolicy::METHOD_PASSWORD);

        if ($this->isLoggedIn() && ($alreadyIn = $this->alreadyLoggedInRedirect()) !== null) {
            return $alreadyIn;
        }

        $type = $this->entityType();

        $this->setGlobalVariable('pageTitle', 'Login');
        $this->setGlobalVariable('captchaSiteKey', $this->getConfig()->offsetGet('captcha')?->siteKey);

        return $this->respond('loginForm', ['entityType' => $type]);
    }

    /**
     * Password login.
     *
     * Goes through the authenticator registry rather than the old provider: the registry is
     * where the per-entity-type lookup, the is-active check and the login-info stamp live,
     * and PasswordAuthenticator existed for a release without anything calling it.
     */
    public function login(): ResponseInterface
    {
        $this->policy->assertEnabled(AuthPolicy::METHOD_PASSWORD);

        $type = $this->entityType();

        try {
            $data = $this->loginFilter->filter($this->getRequest()->getParsedBody());
            $rememberMe = (bool) ($data['rememberMe'] ?? false);
            $entity = $this->authenticatorRegistry->authenticate(
                new PasswordCredentials($data['email'], $data['password'], $type, $rememberMe)
            );
        } catch (\Throwable $e) {
            return $this->fail($e, $this->path('loginForm', $type), filter: $this->loginFilter);
        }

        return $this->completeLogin($entity, $type, $rememberMe);
    }

    /**
     * Finish a login, or divert into the second factor.
     *
     * Every entry point ends here. Two-factor is checked against the entity type rather than
     * the way they arrived, so switching from a password to a magic link cannot shed it; an
     * account that owes a second factor but has never enrolled is sent to set one up rather
     * than waved through.
     */
    private function completeLogin(
        AuthenticatableInterface $entity,
        string $entityType,
        bool $rememberMe = false,
    ): ResponseInterface {
        if ($this->twoFactorService?->isRequiredFor($entity)) {
            $this->pendingAuthentication->begin(
                $entityType,
                $entity->getId(),
                $rememberMe,
                $entity->getRedirectPath()
            );

            return $this->redirect(
                $this->twoFactorService->isEnrolled($entityType, $entity->getId())
                    ? $this->path('twoFactorForm', $entityType)
                    : $this->path('twoFactorSetup', $entityType)
            );
        }

        $this->pendingAuthentication->clear();
        $this->loginService->login($entity, $entityType, $rememberMe);
        $this->getFlash()->success(static::LOGIN_SUCCESS);

        return $this->redirect($entity->getRedirectPath());
    }

    // ---- second factor -------------------------------------------------------------

    /**
     * Ask for the code.
     *
     * Everything this step needs comes from the pending state written when the first factor
     * passed; nothing is read from the request. That is the whole security property of the
     * step — a form field naming the account would let whoever posts it choose one.
     */
    public function twoFactorForm(): ResponseInterface
    {
        $this->assertTwoFactorEnabled();

        if (!$this->twoFactorReady()) {
            return $this->redirect($this->defaultLoginPath($this->entityType()));
        }

        $this->setGlobalVariable('pageTitle', 'Two-factor authentication');

        return $this->respond('twoFactor', ['entityType' => $this->pendingAuthentication->getEntityType()]);
    }

    public function twoFactor(): ResponseInterface
    {
        $this->assertTwoFactorEnabled();

        if (!$this->twoFactorReady()) {
            return $this->redirect($this->defaultLoginPath($this->entityType()));
        }

        $entityType = (string) $this->pendingAuthentication->getEntityType();
        $entityId = $this->pendingAuthentication->getEntityId();
        $params = (array) $this->getRequest()->getParsedBody();

        $back = $this->path('twoFactorForm', $entityType);

        try {
            $this->requireCsrf($params);
            $this->twoFactorService->verify($entityType, $entityId, (string) ($params['code'] ?? ''));
        } catch (\Throwable $e) {
            // The two-factor messages are written for the person holding the phone -- "that
            // code has already been used", "try again later" -- so they are shown as thrown.
            return $this->fail($e, $back, [InvalidCredentials::class => null]);
        }

        return $this->establishPendingSession($entityType, $entityId);
    }

    /**
     * Enrol an authenticator.
     *
     * The secret is issued once and then held in the pending state, so a refresh or a
     * mistyped confirmation shows the same QR code. Re-issuing on every render would rotate
     * the secret under a user who is halfway through scanning it, and they would never get a
     * code that worked.
     */
    public function twoFactorSetup(): ResponseInterface
    {
        $this->assertTwoFactorEnabled();

        if (!$this->twoFactorReady()) {
            return $this->redirect($this->defaultLoginPath($this->entityType()));
        }

        $entityType = (string) $this->pendingAuthentication->getEntityType();
        $entityId = $this->pendingAuthentication->getEntityId();

        if ($this->twoFactorService->isEnrolled($entityType, $entityId)) {
            return $this->redirect($this->path('twoFactorForm', $entityType));
        }

        $entity = $this->loadPendingEntity($entityType, $entityId);
        if (!$entity) {
            return $this->abandonPending($entityType);
        }

        $issued = $this->pendingAuthentication->getEnrolmentSecret();
        $enrolment = $issued
            ? $this->twoFactorService->describeEnrolment($issued, $entity)
            : $this->twoFactorService->beginEnrolment($entityType, $entity);
        $this->pendingAuthentication->rememberEnrolmentSecret($enrolment->secret);

        $this->setGlobalVariable('pageTitle', 'Set up two-factor authentication');

        return $this->respond('twoFactorSetup', [
            'entityType' => $entityType,
            'secret' => $enrolment->secret,
            'provisioningUri' => $enrolment->provisioningUri,
            'qrImage' => $enrolment->qrCodeDataUri,
        ]);
    }

    public function twoFactorSetupConfirm(): ResponseInterface
    {
        $this->assertTwoFactorEnabled();

        if (!$this->twoFactorReady()) {
            return $this->redirect($this->defaultLoginPath($this->entityType()));
        }

        $entityType = (string) $this->pendingAuthentication->getEntityType();
        $entityId = $this->pendingAuthentication->getEntityId();
        $params = (array) $this->getRequest()->getParsedBody();

        $back = $this->path('twoFactorSetup', $entityType);

        try {
            $this->requireCsrf($params);
            $this->twoFactorService->confirmEnrolment($entityType, $entityId, (string) ($params['code'] ?? ''));
        } catch (\Throwable $e) {
            // The two-factor messages are written for the person holding the phone -- "that
            // code has already been used", "try again later" -- so they are shown as thrown.
            return $this->fail($e, $back, [InvalidCredentials::class => null]);
        }

        $this->getFlash()->success(static::TWO_FACTOR_ENABLED);

        return $this->establishPendingSession($entityType, $entityId);
    }

    /** Is there a live challenge, and something to judge it with? */
    private function twoFactorReady(): bool
    {
        return $this->twoFactorService !== null
            && $this->pendingAuthentication->isPending()
            && !$this->isLoggedIn();
    }

    /**
     * Turn a completed challenge into a session.
     *
     * The entity is re-read here rather than carried through the challenge: it may have been
     * deactivated in between, and a stale copy in the session would not show it.
     */
    private function establishPendingSession(string $entityType, int|string|null $entityId): ResponseInterface
    {
        $entity = $this->loadPendingEntity($entityType, $entityId);
        if (!$entity) {
            return $this->abandonPending($entityType);
        }

        if (!$entity->isActive()) {
            return $this->abandonPending($entityType, static::LOGIN_ERROR_INACTIVE);
        }

        $rememberMe = $this->pendingAuthentication->shouldRememberMe();
        $redirect = $this->pendingAuthentication->getRedirectPath() ?: $entity->getRedirectPath();

        $this->pendingAuthentication->clear();
        $this->loginService->login($entity, $entityType, $rememberMe);
        $this->getFlash()->success(static::LOGIN_SUCCESS);

        return $this->redirect($redirect);
    }

    private function loadPendingEntity(string $entityType, int|string|null $entityId): ?AuthenticatableInterface
    {
        if ($entityId === null || !$this->entityRegistry->has($entityType)) {
            return null;
        }

        try {
            $entity = $this->entityRegistry->getRepository($entityType)->getById($entityId);
        } catch (\Exception) {
            return null;
        }

        return $entity instanceof AuthenticatableInterface ? $entity : null;
    }

    /**
     * Null rather than self::GENERIC_ERROR as the default: a parameter default is a constant
     * expression, so self:: would pin this class's English even for a subclass that
     * redeclares the constant. Resolved in the body, where static:: works. Same reason as
     * failures() above.
     */
    private function abandonPending(string $entityType, ?string $message = null): ResponseInterface
    {
        $this->pendingAuthentication->clear();
        $this->getFlash()->error($message ?? static::GENERIC_ERROR);

        return $this->redirect($this->defaultLoginPath($entityType));
    }

    // ---- magic link ----------------------------------------------------------------

    public function magicLinkForm(): ResponseInterface
    {
        $this->policy->assertEnabled(AuthPolicy::METHOD_MAGIC_LINK);

        if ($this->isLoggedIn() && ($alreadyIn = $this->alreadyLoggedInRedirect()) !== null) {
            return $alreadyIn;
        }

        $type = $this->entityType();

        $this->setGlobalVariable('pageTitle', $this->translate('Login'));
        $this->setGlobalVariable('sent', isset($this->getRequest()->getQueryParams()['sent']));

        return $this->respond('magicLinkForm', ['entityType' => $type]);
    }

    /**
     * Issue a link.
     *
     * The entity type comes from the route, never from the posted body. It used to be read
     * from the form, which let anyone turn a delegate login form into a request for a staff
     * link simply by editing a hidden field.
     */
    public function requestMagicLink(): ResponseInterface
    {
        $this->policy->assertEnabled(AuthPolicy::METHOD_MAGIC_LINK);

        $type = $this->entityType();
        $back = $this->path('magicLinkForm', $type);
        $params = (array) $this->getRequest()->getParsedBody();

        try {
            $this->requireCsrf($params);
            $this->magicLinkService->requestMagicLink(
                $this->requireEmail($params),
                $type,
                true,
                !empty($params['rememberMe'])
            );
        } catch (\Throwable $e) {
            // "Not active" rather than "invalid credentials": nothing was checked against a
            // credential here, the account is simply barred.
            return $this->fail($e, $back, [InvalidCredentials::class => static::LOGIN_ERROR_INACTIVE]);
        }

        $this->getFlash()->success($this->translate(static::MAGIC_LINK_SENT));

        return $this->redirect($this->path('magicLinkForm', $type) . '?sent');
    }

    /**
     * Follow a link: confirm on GET, spend on POST.
     *
     * The split is not ceremony. The token is single-use, and consuming it on GET meant
     * anything that merely fetched the URL destroyed it — mobile mail clients and in-app
     * browsers prefetch links to build previews, and tokens were being marked used seconds
     * after being issued, on phones only. Prefetchers issue GET and never POST, so splitting
     * the two makes this immune rather than merely less likely.
     */
    public function verifyMagicLink(): ResponseInterface
    {
        $this->policy->assertEnabled(AuthPolicy::METHOD_MAGIC_LINK);

        $type = $this->entityType();
        $back = $this->path('magicLinkForm', $type);
        $dead = [InvalidCredentials::class => static::MAGIC_LINK_INVALID];

        $token = $this->isPost()
            ? (string) (((array) $this->getRequest()->getParsedBody())['token'] ?? '')
            : (string) ($this->getRequest()->getAttribute('token')
                ?? $this->getRequest()->getQueryParams()['token']
                ?? '');

        if ($token === '') {
            $this->getFlash()->error($this->translate(static::MAGIC_LINK_INVALID));

            return $this->redirect($back);
        }

        if (!$this->isPost()) {
            try {
                // Reads the token without spending it, so a dead link says so before the
                // visitor clicks rather than after.
                $this->magicLinkService->peek($token);
            } catch (\Throwable $e) {
                return $this->fail($e, $back, $dead);
            }

            $this->setGlobalVariable('pageTitle', $this->translate('Login'));

            return $this->respond('confirmMagicLink', ['token' => $token, 'entityType' => $type]);
        }

        try {
            $rememberMe = $this->magicLinkService->shouldRemember($token);
            $entity = $this->authenticatorRegistry->authenticate(new MagicLinkCredentials($token, $type));
        } catch (\Throwable $e) {
            return $this->fail($e, $back, $dead);
        }

        return $this->completeLogin($entity, $type, $rememberMe);
    }

    // ---- leaving -------------------------------------------------------------------

    /**
     * Logout action.
     *
     * The entity type is read before the session is destroyed, so the visitor lands on the
     * door they came in through, and the door itself comes from the policy rather than a
     * hand-kept URL map -- an app with passwords switched off has no loginForm to return to.
     */
    public function logOut(): ResponseInterface
    {
        $entityType = $this->getSession()->getStorage()->offsetGet('loggedInEntityType')
            ?: static::DEFAULT_ENTITY_TYPE;

        $this->pendingAuthentication->clear();
        $this->loginService->logout();
        $this->getFlash()->success(static::LOGGED_OUT);

        return $this->redirect($this->defaultLoginPath($entityType));
    }

    // ---- forgotten passwords -------------------------------------------------------

    public function forgotPasswordForm(): ResponseInterface
    {
        $this->policy->assertEnabled(AuthPolicy::METHOD_PASSWORD);

        if ($this->isLoggedIn() && ($alreadyIn = $this->alreadyLoggedInRedirect()) !== null) {
            return $alreadyIn;
        }

        $type = $this->entityType();
        $this->setGlobalVariable('pageTitle', $this->translate('Reset password page'));
        $this->setGlobalVariable('captchaSiteKey', $this->getConfig()->offsetGet('captcha')?->siteKey);

        return $this->respond('forgotPassword', [
            'entityType' => $type,
            'sent' => isset($this->getRequest()->getQueryParams()['sent']),
        ]);
    }

    public function forgotPassword(): ResponseInterface
    {
        $this->policy->assertEnabled(AuthPolicy::METHOD_PASSWORD);

        if ($this->isLoggedIn() && ($alreadyIn = $this->alreadyLoggedInRedirect()) !== null) {
            return $alreadyIn;
        }

        $type = $this->entityType();

        try {
            $data = $this->forgotPasswordFilter->filter($this->getRequest()->getParsedBody());
        } catch (\Throwable $e) {
            return $this->fail(
                $e,
                $this->path('forgotPasswordForm', $type),
                filter: $this->forgotPasswordFilter
            );
        }

        try {
            $this->loginService->sendForgotToken($data['email'], $type);
        } catch (NotFoundException) {
            // Deliberately indistinguishable from success. Unlike the magic-link form, this
            // one is reachable for accounts that do have passwords, so confirming which
            // addresses exist here would be handing over half of a credential pair.
        }

        return $this->redirect($this->path('forgotPasswordForm', $type) . '?sent');
    }

    public function resetPasswordForm(): ResponseInterface
    {
        $this->policy->assertEnabled(AuthPolicy::METHOD_PASSWORD);

        if ($this->isLoggedIn() && ($alreadyIn = $this->alreadyLoggedInRedirect()) !== null) {
            return $alreadyIn;
        }

        $type = $this->entityType();
        $this->setGlobalVariable('pageTitle', $this->translate('Reset password page'));
        $this->setGlobalVariable('captchaSiteKey', $this->getConfig()->offsetGet('captcha')?->siteKey);

        $hash = (string) $this->getRequest()->getAttribute('token');
        $error = false;
        try {
            $this->loginService->verifyToken($hash, $type);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        return $this->respond('resetForm', ['token' => $hash, 'entityType' => $type, 'error' => $error]);
    }

    public function resetPassword(): ResponseInterface
    {
        $this->policy->assertEnabled(AuthPolicy::METHOD_PASSWORD);

        if ($this->isLoggedIn() && ($alreadyIn = $this->alreadyLoggedInRedirect()) !== null) {
            return $alreadyIn;
        }

        $type = $this->entityType();
        $hash = (string) $this->getRequest()->getAttribute('token');

        $back = sprintf('%s%s/', $this->path('resetPasswordForm', $type), $hash);

        try {
            $tokenData = $this->loginService->verifyToken($hash, $type);
            $data = $this->resetPasswordFilter->filter($this->getRequest()->getParsedBody());
            $this->loginService->resetPassword($tokenData['entityId'], $data['password']);
        } catch (\Throwable $e) {
            // InvalidResetToken says "that link has expired", which is exactly what the
            // visitor needs; shown as thrown rather than flattened to a generic error.
            return $this->fail(
                $e,
                $back,
                [InvalidResetToken::class => null],
                $this->resetPasswordFilter
            );
        }

        $this->forgotPasswordRepository->resetToken($tokenData['tokenId']);
        $this->getFlash()->success(
            $this->translate('You have successfully reset your password. You can now login.')
        );

        return $this->redirect($this->path('loginForm', $type));
    }

    // ---- single sign-on ------------------------------------------------------------

    /**
     * Start an OAuth handshake.
     *
     * The state token is generated once, here, and kept in the session for the callback to
     * compare against. redirectUri is where the visitor was heading before being bounced.
     */
    public function sso(): ResponseInterface
    {
        $this->policy->assertEnabled(AuthPolicy::METHOD_SSO);

        $storage = $this->getSession()->getStorage();
        $storage->offsetSet('redirectUri', $this->getRequest()->getQueryParams()['redirectUri'] ?? null);
        $storage->offsetSet('SSO_STATE', $this->loginService->getSsoState());

        return $this->redirect($this->loginService->getAuthorizationUrl());
    }

    /**
     * Return leg of the OAuth handshake.
     *
     * Previously called LoginService::login() with the raw query parameters, which has taken
     * ($model, $entityType, $rememberMe) since multi-entity login landed — so this path threw
     * a TypeError before it could authenticate anyone, and the catch turned that into a
     * flash message with no text in it.
     *
     * The state is compared against the one issued by sso(). Without that check the callback
     * accepts a code obtained anywhere, which is the whole reason state exists.
     */
    public function ssoCallback(): ResponseInterface
    {
        $this->policy->assertEnabled(AuthPolicy::METHOD_SSO);

        if ($this->isLoggedIn() && ($alreadyIn = $this->alreadyLoggedInRedirect()) !== null) {
            return $alreadyIn;
        }

        $type = $this->entityType();
        $storage = $this->getSession()->getStorage();
        $query = $this->getRequest()->getQueryParams();
        $expectedState = $storage->offsetGet('SSO_STATE');

        $state = (string) ($query['state'] ?? '');
        if (!is_string($expectedState) || $expectedState === '' || !hash_equals($expectedState, $state)) {
            $storage->offsetUnset('SSO_STATE');
            $this->getFlash()->error(static::GENERIC_ERROR);

            return $this->redirect($this->defaultLoginPath($type));
        }

        try {
            $entity = $this->authenticatorRegistry->authenticate(new SsoCredentials(
                (string) ($query['provider'] ?? $this->getConfig()->sso?->provider ?? 'Google'),
                (string) ($query['code'] ?? ''),
                $state,
                $type
            ));
        } catch (\Throwable $e) {
            return $this->fail($e, $this->defaultLoginPath($type));
        } finally {
            $storage->offsetUnset('SSO_STATE');
        }

        $redirectUri = $storage->offsetGet('redirectUri');
        $storage->offsetUnset('redirectUri');

        $response = $this->completeLogin($entity, $type);

        return $redirectUri && $this->isLoggedIn() ? $this->redirect($redirectUri) : $response;
    }
}
