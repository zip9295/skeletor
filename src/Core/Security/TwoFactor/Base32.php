<?php

declare(strict_types=1);

namespace Skeletor\Core\Security\TwoFactor;

/**
 * RFC 4648 base32 codec, used only for TOTP shared secrets.
 *
 * Authenticator apps take the secret as base32 in the otpauth:// URI, so a codec is
 * unavoidable; PHP ships base64 but not base32. Twenty lines of table lookup is a poor
 * reason to take a dependency, and the alphabet is fixed by the RFC so there is nothing
 * here to get creatively wrong.
 *
 * Decoding is deliberately strict: an unknown character throws rather than being skipped.
 * A silently-dropped character yields a different secret, which fails as "wrong code"
 * every 30 seconds forever instead of as "you pasted a bad secret" once.
 */
final class Base32
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function encode(string $bytes, bool $pad = false): string
    {
        if ($bytes === '') {
            return '';
        }

        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        if ($pad) {
            $out = str_pad($out, (int) (ceil(strlen($out) / 8) * 8), '=');
        }

        return $out;
    }

    /**
     * @throws \InvalidArgumentException on any character outside the RFC 4648 alphabet
     */
    public static function decode(string $encoded): string
    {
        // Padding carries no information, and humans retyping a secret lose it first.
        $encoded = rtrim(strtoupper(preg_replace('/\s+/', '', $encoded) ?? ''), '=');
        if ($encoded === '') {
            return '';
        }

        $bits = '';
        foreach (str_split($encoded) as $char) {
            $index = strpos(self::ALPHABET, $char);
            if ($index === false) {
                throw new \InvalidArgumentException(sprintf('"%s" is not valid base32.', $char));
            }
            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        // The trailing partial group is padding bits, not a byte — dropping it is what
        // makes decode(encode($x)) === $x for lengths that are not a multiple of 5.
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $out .= chr(bindec($chunk));
            }
        }

        return $out;
    }
}
