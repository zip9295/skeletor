<?php

declare(strict_types=1);

namespace Skeletor\Core\Security\TwoFactor;

/**
 * What the setup page needs to show, and nothing else.
 *
 * The bare secret is present because manual entry has to stay possible when there is no QR
 * renderer — which makes this object sensitive: it is safe to hand to the enrolling user's
 * own template and to nothing else. It is deliberately not stored, logged or serialised.
 */
final class TwoFactorEnrolment
{
    public function __construct(
        public readonly string $secret,
        public readonly string $provisioningUri,
        public readonly ?string $qrCodeDataUri,
    ) {}
}
