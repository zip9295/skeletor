<?php

declare(strict_types=1);

namespace Skeletor\Core\Security;

use Skeletor\Core\Config\Config;
use Skeletor\Core\Login\Exception\MethodNotEnabled;
use Skeletor\Core\Security\Authentication\AuthenticatableInterface;

/**
 * Which auth mechanisms this application has, and whether they cost a second factor.
 *
 * Config, in full:
 *
 *     'auth' => [
 *         'methods'   => ['magic_link'],  // ways in that are switched on
 *         'default'   => 'magic_link',    // which form is "the" login page
 *         'twoFactor' => false,           // whole-app switch
 *     ],
 *
 * The setting is per application, not per entity type
 *
 * With no 'auth' node at all this reports password + magic link, defaulting to password,
 * and two-factor off.
 */
final class AuthPolicy
{
    public const METHOD_PASSWORD = 'password';
    public const METHOD_MAGIC_LINK = 'magic_link';
    public const METHOD_SSO = 'sso';

    public const METHODS = [self::METHOD_PASSWORD, self::METHOD_MAGIC_LINK, self::METHOD_SSO];

    /** What the framework did before this class existed. */
    private const LEGACY_METHODS = [self::METHOD_PASSWORD, self::METHOD_MAGIC_LINK];

    /** The form that is the front door for each method. */
    private const ENTRY_ACTION = [
        self::METHOD_PASSWORD => 'loginForm',
        self::METHOD_MAGIC_LINK => 'magicLinkForm',
        self::METHOD_SSO => 'sso',
    ];

    /** @var list<string> */
    private readonly array $methods;

    private readonly string $default;

    private readonly bool $twoFactor;

    public function __construct(Config $config)
    {
        $auth = $config->auth;

        $methods = $auth?->methods;
        $methods = $methods instanceof Config ? $methods->toArray() : $methods;
        $methods = is_array($methods) && $methods !== [] ? array_values($methods) : self::LEGACY_METHODS;

        foreach ($methods as $method) {
            if (!in_array($method, self::METHODS, true)) {
                // Loudly, at construction. A typo that silently disabled a login method would
                // present as "the login page 404s" long after the deploy that caused it.
                throw new \InvalidArgumentException(sprintf(
                    'Unknown authentication method "%s" in auth.methods; expected one of: %s',
                    is_scalar($method) ? (string) $method : gettype($method),
                    implode(', ', self::METHODS)
                ));
            }
        }

        $default = $auth?->default;
        if ($default !== null && !in_array($default, $methods, true)) {
            throw new \InvalidArgumentException(sprintf(
                'auth.default is "%s", which is not one of the enabled auth.methods (%s).',
                (string) $default,
                implode(', ', $methods)
            ));
        }

        $this->methods = $methods;
        $this->default = (string) ($default ?? $methods[0]);
        $this->twoFactor = (bool) ($auth?->twoFactor ?? false);
    }

    /** @return list<string> */
    public function methods(): array
    {
        return $this->methods;
    }

    public function isEnabled(string $method): bool
    {
        return in_array($method, $this->methods, true);
    }

    public function defaultMethod(): string
    {
        return $this->default;
    }

    public function twoFactorRequired(): bool
    {
        return $this->twoFactor;
    }

    /**
     * Refuse a method this application does not have.
     *
     * A 404, because a disabled method's endpoint should not exist: WebSkeletor
     * turns it into the app's 404. Answering anything else — a redirect, a validation error —
     * would confirm the endpoint is there and merely switched off.
     *
     * @throws MethodNotEnabled
     */
    public function assertEnabled(string $method): void
    {
        if (!$this->isEnabled($method)) {
            throw new MethodNotEnabled(sprintf('Authentication method "%s" is not enabled.', $method));
        }
    }

    /**
     * Can this particular account use this method?
     *
     * Config decides what the application offers; the account can only narrow it, never widen
     * it. That direction matters: an entity class that could switch a method back on would be
     * a way around the policy, and supportsAuthenticator() is written on entities, which is
     * exactly where an oversight is least visible.
     */
    public function availableTo(string $method, AuthenticatableInterface $entity): bool
    {
        return $this->isEnabled($method) && $entity->supportsAuthenticator($method);
    }

    /**
     * The path of the front door, for the given entity type.
     *
     * Derived rather than configured, so there is no second list of URLs to keep in step with
     * the enabled methods — which is what the old loginUrls map was.
     */
    public function loginPath(string $entityType): string
    {
        return sprintf('/login/%s/%s/', $entityType, self::ENTRY_ACTION[$this->default]);
    }
}
