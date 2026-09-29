<?php

declare(strict_types=1);

namespace Skeletor\Tests\Support;

use Skeletor\Core\Config\Config;
use Skeletor\Core\Security\AuthPolicy;

/**
 * Builds an AuthPolicy from the same config shape an application writes.
 *
 * Deliberately goes through Config rather than exposing setters: the thing worth testing is
 * that a policy written the way an app writes it produces the behaviour the app expects, and
 * a builder that bypassed the config parsing would test neither.
 */
final class Policies
{
    /**
     * @param list<string> $methods
     */
    public static function with(array $methods, ?string $default = null, bool $twoFactor = false): AuthPolicy
    {
        $auth = ['methods' => $methods, 'twoFactor' => $twoFactor];
        if ($default !== null) {
            $auth['default'] = $default;
        }

        return new AuthPolicy(new Config(['auth' => $auth]));
    }

    /** What an app gets when it has no auth config at all. */
    public static function legacyDefault(): AuthPolicy
    {
        return new AuthPolicy(new Config([]));
    }

    /** Everything on, so a test can concentrate on something other than the policy. */
    public static function everything(bool $twoFactor = false): AuthPolicy
    {
        return self::with(AuthPolicy::METHODS, AuthPolicy::METHOD_PASSWORD, $twoFactor);
    }
}
