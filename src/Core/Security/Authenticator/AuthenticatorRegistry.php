<?php
declare(strict_types=1);

namespace Skeletor\Core\Security\Authenticator;

use Skeletor\Core\Security\Authentication\AuthenticatableInterface;
use Skeletor\Core\Security\Authentication\CredentialsInterface;
use Skeletor\Core\Security\AuthPolicy;
use Skeletor\Core\Login\Exception\AuthMethodDisabled;

/**
 * Routes credentials to the authenticator that handles them, and refuses the ones this
 * application has switched off.
 *
 * This is the backstop, not the friendly gate — LoginController 404s a disabled method's
 * endpoint long before anything reaches here. It exists because that gate is one route table
 * and one ACL file away from being wrong, and the failure mode of a wrong gate is a working
 * login for a method the application does not intend to offer. Every path to a session goes
 * through this method, so this is the one place where "off" can be made to mean off.
 */
class AuthenticatorRegistry
{
    /** @var array<string, AuthenticatorInterface> keyed by the method each one handles */
    private array $authenticators = [];

    public function __construct(
        private readonly AuthPolicy $policy,
        PasswordAuthenticator $passwordAuthenticator,
        MagicLinkAuthenticator $magicLinkAuthenticator,
        ?SsoAuthenticator $ssoAuthenticator = null
    ) {
        $this->addAuthenticator($passwordAuthenticator);
        $this->addAuthenticator($magicLinkAuthenticator);

        if ($ssoAuthenticator) {
            $this->addAuthenticator($ssoAuthenticator);
        }

        $this->assertEveryEnabledMethodIsWired();
    }

    /**
     * Add an authenticator to the registry
     */
    public function addAuthenticator(AuthenticatorInterface $authenticator): void
    {
        $this->authenticators[$authenticator->handles()] = $authenticator;
    }

    /**
     * Authenticate using appropriate authenticator based on credentials
     *
     * @throws AuthMethodDisabled when the method is not enabled for this application
     */
    public function authenticate(CredentialsInterface $credentials): AuthenticatableInterface
    {
        $type = $credentials->getType();

        if (!$this->policy->isEnabled($type)) {
            throw new AuthMethodDisabled(sprintf(
                'Authentication method "%s" is not enabled for this application.',
                $type
            ));
        }

        $authenticator = $this->authenticators[$type] ?? null;
        if (!$authenticator || !$authenticator->supports($credentials)) {
            throw new \RuntimeException(
                sprintf('No authenticator found for credentials type: %s', $type)
            );
        }

        return $authenticator->authenticate($credentials);
    }

    /**
     * Get specific authenticator by class
     */
    public function getAuthenticator(string $class): ?AuthenticatorInterface
    {
        foreach ($this->authenticators as $authenticator) {
            if ($authenticator instanceof $class) {
                return $authenticator;
            }
        }

        return null;
    }

    /**
     * A method switched on in config with nothing behind it is a deployment mistake, not a
     * runtime condition: the login page would render and then fail on submit. Caught here so
     * it surfaces when the container is built.
     */
    private function assertEveryEnabledMethodIsWired(): void
    {
        $missing = array_diff($this->policy->methods(), array_keys($this->authenticators));

        if ($missing !== []) {
            throw new \LogicException(sprintf(
                'auth.methods enables %s, but no authenticator is wired for %s.',
                implode(', ', $this->policy->methods()),
                implode(', ', $missing)
            ));
        }
    }
}
