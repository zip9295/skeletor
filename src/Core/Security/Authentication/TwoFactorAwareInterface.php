<?php

declare(strict_types=1);

namespace Skeletor\Core\Security\Authentication;

/**
 * Opt-in marker for entities that decide their own two-factor requirement.
 *
 * Most apps switch two-factor on per entity type in config, which needs no code on the
 * entity at all. This exists for the case config cannot express — "admins must, everyone
 * else may" — and takes precedence over the config list when implemented.
 */
interface TwoFactorAwareInterface
{
    /**
     * True when this particular account must pass a second factor to log in.
     *
     * Note this is about the requirement, not about enrolment: an account that must use
     * two-factor but has not enrolled yet is sent to the setup page rather than let in.
     */
    public function requiresTwoFactor(): bool;
}
