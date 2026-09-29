<?php

declare(strict_types=1);

namespace Skeletor\Core\Security\TwoFactor;

/**
 * RFC 6238 time-based one-time passwords (and the RFC 4226 HOTP truncation under them).
 *
 * Written out rather than pulled in because the whole algorithm is hash_hmac() plus a
 * documented truncation, and PHP already ships the only primitive that matters. The
 * framework must not force a dependency on every app for a feature most of them will
 * never switch on — see QrCodeRendererInterface for the same reasoning applied to the
 * one piece that genuinely is worth a library.
 *
 * Correctness is pinned against the official RFC 6238 Appendix B test vectors in
 * TotpGeneratorTest; if this file is ever touched, those vectors are the acceptance test.
 */
class TotpGenerator
{
    public const ALGORITHM_SHA1 = 'sha1';
    public const ALGORITHM_SHA256 = 'sha256';
    public const ALGORITHM_SHA512 = 'sha512';

    public function __construct(
        private readonly int $digits = 6,
        private readonly int $period = 30,
        private readonly string $algorithm = self::ALGORITHM_SHA1,
    ) {
        if ($this->digits < 6 || $this->digits > 10) {
            throw new \InvalidArgumentException('TOTP digits must be between 6 and 10.');
        }
        if ($this->period < 1) {
            throw new \InvalidArgumentException('TOTP period must be at least one second.');
        }
        if (!in_array($this->algorithm, hash_hmac_algos(), true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported HMAC algorithm "%s".', $this->algorithm));
        }
    }

    /**
     * A fresh shared secret, base32-encoded for the authenticator app.
     *
     * 20 bytes is the RFC 4226 recommendation and what every authenticator expects; longer
     * secrets are legal but some apps silently truncate them, which fails as an invalid
     * code rather than as an error.
     */
    public function generateSecret(int $bytes = 20): string
    {
        if ($bytes < 16) {
            throw new \InvalidArgumentException('A TOTP secret must be at least 16 bytes.');
        }

        return Base32::encode(random_bytes($bytes));
    }

    /**
     * The code for the counter window containing $timestamp (default: now).
     */
    public function at(string $secret, ?int $timestamp = null): string
    {
        return $this->atCounter($secret, intdiv($timestamp ?? time(), $this->period));
    }

    /**
     * Verify a submitted code against the secret.
     *
     * $window is how many periods either side of now are accepted, absorbing clock drift
     * between the server and the user's phone. Every candidate is compared even after a
     * match so the loop takes the same time whichever window hit — a timing signal here
     * would leak how far off the clock is, which narrows a brute force.
     *
     * The caller is responsible for rate limiting: a six-digit code is one in a million,
     * which is only a meaningful barrier while the number of attempts is bounded.
     * TwoFactorService does that.
     */
    public function verify(string $secret, string $code, ?int $timestamp = null, int $window = 1): bool
    {
        return $this->matchCounter($secret, $code, $timestamp, $window) !== null;
    }

    /**
     * The counter a code matched, or null if it matched none.
     *
     * Callers that guard against replay need to know *which* window was accepted, not just
     * that one was — a code stays valid for its whole period, so "already spent" can only be
     * decided against the counter. See TwoFactorSecret::hasSpent().
     */
    public function matchCounter(string $secret, string $code, ?int $timestamp = null, int $window = 1): ?int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if ($code === '' || strlen($code) !== $this->digits || !ctype_digit($code)) {
            return null;
        }

        $counter = intdiv($timestamp ?? time(), $this->period);
        $matched = null;
        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals($this->atCounter($secret, $counter + $offset), $code)) {
                $matched = $counter + $offset;
            }
        }

        return $matched;
    }

    /**
     * The otpauth:// URI an authenticator app consumes, normally via a QR code.
     *
     * Both label halves are encoded, and the issuer is repeated as a parameter as well as
     * in the label — the prefix is what old apps read, the parameter is what current ones
     * read, and Google Authenticator shows the account twice if they disagree.
     */
    public function getProvisioningUri(string $secret, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($account);

        $params = [
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => strtoupper($this->algorithm),
            'digits' => (string) $this->digits,
            'period' => (string) $this->period,
        ];

        return sprintf('otpauth://totp/%s?%s', $label, http_build_query($params, '', '&', PHP_QUERY_RFC3986));
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function getDigits(): int
    {
        return $this->digits;
    }

    /**
     * RFC 4226 section 5: HMAC the counter as an 8-byte big-endian integer, then take four
     * bytes starting at the offset held in the low nibble of the last byte.
     */
    private function atCounter(string $secret, int $counter): string
    {
        $key = Base32::decode($secret);
        if ($key === '') {
            throw new \InvalidArgumentException('TOTP secret is empty.');
        }

        // pack('J') is 64-bit big-endian, which is exactly the RFC's 8-byte counter.
        $hash = hash_hmac($this->algorithm, pack('J', $counter), $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $truncated = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($truncated % (10 ** $this->digits)), $this->digits, '0', STR_PAD_LEFT);
    }
}
