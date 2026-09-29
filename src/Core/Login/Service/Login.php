<?php
namespace Skeletor\Core\Login\Service;

use Laminas\Session\ManagerInterface;
use Skeletor\Core\Mailer\Service\MailerInterface;
use Skeletor\Core\Mailer\Service\PhpMailer;
use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Core\Security\Authentication\PasswordCredentials;
use Skeletor\Core\Security\Authenticator\AuthenticatorRegistry;
use Skeletor\Core\Security\EntityRegistry;
use Skeletor\Core\Login\Exception\InvalidCredentials;
use Skeletor\Core\Login\Exception\InvalidResetToken;
use Skeletor\Core\Login\Provider\ProviderInterface;
use Skeletor\Core\Login\Repository\ForgotPasswordRepository;

class Login
{
    /** Hours a password-reset link stays usable. */
    public const RESET_TOKEN_TTL_HOURS = 3;

    protected $message;

    /**
     * $provider is optional. It is the pre-authenticator password path, and an app with no
     * password column anywhere — solidarity, which is magic-link only — has nothing sensible
     * to put here; it was previously forced to wire a DbProvider against an entity whose
     * getPassword() could only ever return null.
     *
     * $entityRegistry is what makes forgot-password work for a type other than "user".
     */
    public function __construct(
        public readonly ?ProviderInterface $provider,
        private ManagerInterface          $session,
        private MailerInterface           $mailer,
        private ForgotPasswordRepository  $tokenRepo,
        private ?AuthenticatorRegistry    $authenticatorRegistry = null,
        private ?EntityRegistry           $entityRegistry = null
    ) {
    }

    /**
     * @throws \LogicException when a password operation is attempted in an app that wired no
     *         provider — a clearer failure than a null-dereference three frames down.
     */
    private function requireProvider(): ProviderInterface
    {
        if (!$this->provider) {
            throw new \LogicException(
                'No login provider is configured; this application does not support password authentication.'
            );
        }

        return $this->provider;
    }

    public function resetPassword($userId, $password)
    {
        $this->requireProvider()->updatePassword($userId, $password);
    }

    /**
     * Mail a password-reset token.
     *
     * $entityType is recorded on the token rather than the hardcoded 1 it used to carry, so
     * two entity types that happen to share an id cannot resolve each other's tokens.
     */
    public function sendForgotToken($email, string $entityType = 'user')
    {
        $user = $this->getByEmail($email, $entityType);
        $pwd = $this->generateForgotPasswordHash();
        $displayName = '';
        if(method_exists($user, 'getDisplayName')) {
            $displayName = $user->getDisplayName();
        } elseif(method_exists($user, 'getFirstName') && method_exists($user, 'getLastName')) {
            $displayName = sprintf('%s %s',$user->getFirstName(), $user->getLastName());
        }
        $this->mailer->sendForgotPasswordMail($user->getEmail(), $pwd, $displayName, $user->getId());
        $this->tokenRepo->create([
            'entityId' => $user->getId(),
            'entityType' => $entityType,
            'token' => password_hash($pwd, PASSWORD_BCRYPT),
        ]);
    }

    public function generateForgotPasswordHash()
    {
        return md5(sprintf(
            '%s%s%s', microtime(true), random_int(PHP_INT_MIN, PHP_INT_MAX), random_bytes(1024)
        ));
    }

    /**
     * Check a reset link and say whose it is.
     *
     * The link carries "$<entityId>$<token>"; the entity type comes from the caller, because
     * the route knows it and the URL should not be able to choose it.
     *
     * The three-hour limit is now enforced. It was computed and then never compared against
     * anything, so a reset link stayed valid forever: one old message in a mailbox, or one
     * forwarded thread, was a permanent way back into the account.
     *
     * @throws InvalidResetToken for a missing, expired, spent or wrong token — deliberately the same
     *         message for all of them, so this cannot be used to probe which addresses have
     *         pending resets.
     */
    public function verifyToken($hash, string $entityType = 'user', int $ttlHours = self::RESET_TOKEN_TTL_HOURS)
    {
        $data = explode('$', (string) $hash);
        if (count($data) < 3 || $data[1] === '' || $data[2] === '') {
            throw new InvalidResetToken('A non existing or expired token was submitted.');
        }
        $verifyToken = $data[2];
        $entityId = $data[1];

        // @todo could do this via query
        $token = $this->tokenRepo->fetchAll(
            ['entityId' => $entityId, 'entityType' => $entityType],
            1,
            ['createdAt' => 'DESC']
        );
        if (!$token || !count($token)) {
            throw new InvalidResetToken('A non existing or expired token was submitted.');
        }
        $token = $token[0];

        $createdAt = $token->getCreatedAt();
        if ($createdAt instanceof \DateTimeInterface
            && $createdAt < (new \DateTime())->modify(sprintf('-%d hours', $ttlHours))
        ) {
            throw new InvalidResetToken('A non existing or expired token was submitted.');
        }

        // resetToken() blanks the column once a reset completes; password_verify against an
        // empty hash is false, but saying so explicitly keeps that from being load-bearing.
        if ($token->token === '' || !password_verify($verifyToken, $token->token)) {
            throw new InvalidResetToken('An invalid token was submitted.');
        }

        return [
            'userId' => $entityId,
            'entityId' => $entityId,
            'entityType' => $entityType,
            'tokenId' => $token->getId(),
        ];
    }

    /**
     * Logs user in and set appropriate session variables
     *
     * @param $model
     * @param $entityType
     * @return void
     */
    public function login($model, $entityType, $rememberMe = false)
    {
        //@TODO update this var if user info changes
        $this->session->regenerateId(true);
        if ($rememberMe) {
            $this->session->rememberMe();
        }
        $this->session->getStorage()->offsetSet('loggedIn', $model->getId());
        $this->session->getStorage()->offsetSet('loggedInRole', method_exists($model, 'getRole') ? $model->getRole() : $model->getAuthRole());
        $this->session->getStorage()->offsetSet('loggedInEmail', $model->getEmail());
        $this->session->getStorage()->offsetSet('loggedInEntityType', $entityType);

        $firstName = method_exists($model, 'getFirstName') ? $model->getFirstName() : null;
        $lastName = method_exists($model, 'getLastName') ? $model->getLastName() : null;

        $this->session->getStorage()->offsetSet('loggedInFirstName', $firstName);
        $this->session->getStorage()->offsetSet('loggedInLastName', $lastName);

        if (property_exists($model, 'tenant') && $model->tenant) {
            $this->session->getStorage()->offsetSet('tenantId', $model->tenant->getId());
        }
        // @TODO allow different naming for tenant filter. should probably wrap this with upper check
        if (method_exists($model, 'getTenant') && $model->getTenant()) {
            $this->session->getStorage()->offsetSet('tenantId', $model->getTenant()->getId());
        }
//        $this->session->getStorage()->offsetSet('user', $model);
        $this->session->getStorage()->offsetSet('redirectPath', $model->getRedirectPath());

        //@TODO add redirect to last page user tried to visit before timeout
//            $this->session->getStorage()->offsetSet('redirectPath', '/');
    }

    public function logout()
    {
        $this->session->getStorage()->offsetUnset('loggedIn');
        $this->session->getStorage()->offsetUnset('loggedInRole');
        $this->session->getStorage()->offsetUnset('redirectPath');
        $this->session->getStorage()->offsetUnset('tenantId');
        $this->session->getStorage()->offsetUnset('loggedInEmail');
        $this->session->getStorage()->offsetUnset('loggedInFirstName');
        $this->session->getStorage()->offsetUnset('loggedInLastName');
        $this->session->getStorage()->offsetUnset('loggedInEntityType');
        $this->session->forgetMe();
        $this->session->destroy();
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getSsoState()
    {
        return $this->requireProvider()->getSsoState();
    }

    public function getAuthorizationUrl()
    {
        return $this->requireProvider()->getAuthorizationUrl();
    }

    /**
     * The account behind an address.
     *
     * Resolved through the entity registry when there is one, so this works for any
     * registered type; falls back to the password provider for apps that predate the
     * registry.
     *
     * @throws NotFoundException when no such account exists
     */
    public function getByEmail($email, string $entityType = 'user')
    {
        if ($this->entityRegistry && $this->entityRegistry->has($entityType)) {
            $entity = $this->entityRegistry->findByEmail($entityType, $email);
            if (!$entity) {
                throw new NotFoundException('Email not found in system');
            }

            return $entity;
        }

        return $this->requireProvider()->getByEmail($email);
    }
}