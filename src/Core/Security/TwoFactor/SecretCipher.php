<?php

declare(strict_types=1);

namespace Skeletor\Core\Security\TwoFactor;

/**
 * Authenticated encryption for TOTP shared secrets at rest.
 *
 * A TOTP secret is a bearer credential: anyone holding it can mint valid codes forever, so
 * it must not sit in the database in the clear next to the account it protects.
 *
 * Three things here are deliberate, because the obvious implementation gets each wrong:
 *
 *  - GCM, not CBC. CBC is unauthenticated, so a database-level attacker can flip ciphertext
 *    bits and the application cannot tell. The GCM tag makes tampering a decryption failure.
 *  - The configured key is run through HKDF rather than handed to OpenSSL directly. AES-256
 *    needs exactly 32 bytes; a shorter passphrase would be silently zero-padded and a longer
 *    one silently truncated, so a memorable config string would quietly become a weak key.
 *  - The IV travels inside the stored envelope. Splitting it into its own column invites the
 *    two halves to drift apart in a partial write or a botched migration, which is
 *    indistinguishable from tampering and unrecoverable.
 *
 * Envelope: "v1.<base64url iv>.<base64url tag>.<base64url ciphertext>". Versioned so a key
 * rotation or cipher change can be recognised rather than guessed at.
 */
class SecretCipher
{
    private const CIPHER = 'aes-256-gcm';
    private const VERSION = 'v1';
    private const IV_BYTES = 12;   // 96 bits, the size GCM is specified for
    private const TAG_BYTES = 16;
    private const HKDF_INFO = 'skeletor:two-factor:secret';

    private readonly string $key;

    public function __construct(#[\SensitiveParameter] string $encryptionKey)
    {
        if (strlen(trim($encryptionKey)) < 16) {
            throw new \InvalidArgumentException(
                'Two-factor encryption key must be at least 16 characters. Set twoFactor.encryptionKey in config.'
            );
        }

        $this->key = hash_hkdf('sha256', $encryptionKey, 32, self::HKDF_INFO);
    }

    public function encrypt(#[\SensitiveParameter] string $plaintext): string
    {
        $iv = random_bytes(self::IV_BYTES);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_BYTES
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Failed to encrypt two-factor secret.');
        }

        return implode('.', [
            self::VERSION,
            self::b64encode($iv),
            self::b64encode($tag),
            self::b64encode($ciphertext),
        ]);
    }

    /**
     * @throws \RuntimeException when the envelope is malformed, truncated, or has been
     *         tampered with — all of which are the same answer to the caller: this secret
     *         can no longer be trusted, re-enrol the account.
     */
    public function decrypt(string $envelope): string
    {
        $parts = explode('.', $envelope);
        if (count($parts) !== 4 || $parts[0] !== self::VERSION) {
            throw new \RuntimeException('Unrecognised two-factor secret envelope.');
        }

        [, $iv, $tag, $ciphertext] = array_map(self::b64decode(...), $parts);

        if (strlen($iv) !== self::IV_BYTES || strlen($tag) !== self::TAG_BYTES) {
            throw new \RuntimeException('Two-factor secret envelope is malformed.');
        }

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($plaintext === false) {
            // Wrong key or altered ciphertext; GCM cannot tell us which, and neither can we.
            throw new \RuntimeException('Two-factor secret could not be decrypted.');
        }

        return $plaintext;
    }

    private static function b64encode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function b64decode(string $encoded): string
    {
        return base64_decode(strtr($encoded, '-_', '+/'), true) ?: '';
    }
}
