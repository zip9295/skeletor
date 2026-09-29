<?php

declare(strict_types=1);

namespace Skeletor\Core\Security\TwoFactor;

/**
 * Turns an otpauth:// URI into something an <img> can point at.
 *
 * Behind an interface because QR rendering is the one part of two-factor that genuinely
 * wants a library, and the apps do not agree on which — a3s has endroid/qr-code, solidarity
 * has chillerlan/php-qrcode. Requiring either in the framework would install a barcode
 * renderer into every app for a feature most of them never switch on.
 *
 * Returning null is a supported answer, not a failure: the setup page falls back to showing
 * the secret for manual entry, which every authenticator app accepts.
 */
interface QrCodeRendererInterface
{
    /**
     * @return string|null a data: URI suitable for an img src, or null if unavailable
     */
    public function render(string $data): ?string;
}
