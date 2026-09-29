<?php
declare(strict_types=1);

namespace Skeletor\Core\Security\Authenticator;

use Laminas\Session\ManagerInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use Monolog\Logger;
use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Core\Security\Authentication\AuthenticatableInterface;
use Skeletor\Core\Security\Authentication\CredentialsInterface;
use Skeletor\Core\Security\Authentication\SsoCredentials;
use Skeletor\Core\Security\AuthPolicy;
use Skeletor\Core\Security\EntityRegistry;

/**
 * Single sign-on via an OAuth2 authorization-code flow.
 *
 * The provider is injected, so which identity provider this talks to is an app-level wiring
 * decision — league/oauth2-client has a driver for most of them, and nothing here is specific
 * to any one.
 *
 * The state parameter is validated by the caller (LoginController) against the value it
 * issued, before this is reached. That check is the entire point of state, and it has to
 * happen where the session that issued it can be read.
 */
class SsoAuthenticator extends AbstractAuthenticator
{
    const TYPE_LINKEDIN = 1;
    const TYPE_FACEBOOK = 2;
    const TYPE_TWITTER = 3;
    const TYPE_GOOGLE = 4;
    const TYPE_INSTAGRAM = 5;

    public function __construct(
        EntityRegistry $entityRegistry,
        AuthPolicy $policy,
        private AbstractProvider $oauthProvider,
        private ManagerInterface $session,
        private Logger $logger
    ) {
        parent::__construct($entityRegistry, $policy);
    }

    public function handles(): string
    {
        return AuthPolicy::METHOD_SSO;
    }

    public function supports(CredentialsInterface $credentials): bool
    {
        return $credentials instanceof SsoCredentials;
    }

    public function authenticate(CredentialsInterface $credentials): AuthenticatableInterface
    {
        if (!$credentials instanceof SsoCredentials) {
            throw new \InvalidArgumentException('SsoAuthenticator requires SsoCredentials');
        }

        $remoteType = match ($credentials->getProvider()) {
            'LinkedIn' => self::TYPE_LINKEDIN,
            'Facebook' => self::TYPE_FACEBOOK,
            'Twitter' => self::TYPE_TWITTER,
            'Google' => self::TYPE_GOOGLE,
            'Instagram' => self::TYPE_INSTAGRAM,
            default => throw new \InvalidArgumentException('Unsupported OAuth provider'),
        };

        try {
            $token = $this->oauthProvider->getAccessToken('authorization_code', [
                'code' => $credentials->getCode()
            ]);

            // The state is validated by the controller against the value it issued, before
            // this is ever called. Writing the state received from the callback back into the
            // session — which is what used to happen here — made that comparison compare the
            // attacker's value with itself.
            $userData = $this->oauthProvider->getResourceOwner($token);

            $repository = $this->getRepository($credentials->getEntityType());
            $entity = $this->entityRegistry->findByEmail($credentials->getEntityType(), $userData->getEmail());

            if (!$entity) {
                // Auto-create from the identity provider's profile, then read it back: create() returns
                // whatever the app's factory decides to return (often just an id), and the
                // caller needs the entity itself.
                $repository->create([
                    'firstName' => $userData->getFirstName(),
                    'lastName' => $userData->getLastName(),
                    'email' => $userData->getEmail(),
                    'isActive' => 1,
                    'remoteType' => $remoteType,
                    'id' => 0,
                    'password' => ''
                ]);

                $entity = $this->entityRegistry->findByEmail(
                    $credentials->getEntityType(),
                    $userData->getEmail()
                );
            }

            if (!$entity instanceof AuthenticatableInterface) {
                throw new \RuntimeException('Entity must implement AuthenticatableInterface');
            }

            $this->assertAvailableTo(AuthPolicy::METHOD_SSO, $entity);
            $this->updateLoginInfo($entity);

            return $entity;

        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            throw $e;
        }
    }

    public function getSsoState(): string
    {
        return $this->oauthProvider->getState();
    }

    public function getAuthorizationUrl(): string
    {
        return $this->oauthProvider->getAuthorizationUrl();
    }
}
