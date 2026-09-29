<?php

declare(strict_types=1);

namespace Skeletor\Core\Security\TwoFactor;

/**
 * The default: no picture, just the secret.
 *
 * Enrolment stays fully usable — every authenticator app can take a typed secret — so an
 * app can adopt two-factor first and choose a QR library later, rather than the other way
 * round.
 */
class NullQrCodeRenderer implements QrCodeRendererInterface
{
    public function render(string $data): ?string
    {
        return null;
    }
}
