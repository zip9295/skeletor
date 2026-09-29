<?php

declare(strict_types=1);

namespace Skeletor\Tests\Core\Security\TwoFactor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Skeletor\Core\Security\TwoFactor\SecretCipher;

/**
 * What must hold for a TOTP secret at rest: unreadable without the key, and unusable if
 * altered. The second half is why this is GCM and not CBC — CBC round-trips just as happily
 * after someone has flipped bits in the database, and the application cannot tell.
 */
#[CoversClass(SecretCipher::class)]
final class SecretCipherTest extends TestCase
{
    private const KEY = 'a-configured-key-long-enough';

    public function testRoundTrips(): void
    {
        $cipher = new SecretCipher(self::KEY);

        self::assertSame('JBSWY3DPEHPK3PXP', $cipher->decrypt($cipher->encrypt('JBSWY3DPEHPK3PXP')));
    }

    public function testTheSameSecretEncryptsDifferentlyEveryTime(): void
    {
        // A fresh IV per encryption. Without it, two accounts sharing a secret would be
        // visibly identical in the database, and the ciphertext would leak that.
        $cipher = new SecretCipher(self::KEY);

        self::assertNotSame($cipher->encrypt('JBSWY3DPEHPK3PXP'), $cipher->encrypt('JBSWY3DPEHPK3PXP'));
    }

    public function testThePlaintextIsNowhereInTheEnvelope(): void
    {
        $cipher = new SecretCipher(self::KEY);

        self::assertStringNotContainsString('JBSWY3DPEHPK3PXP', $cipher->encrypt('JBSWY3DPEHPK3PXP'));
    }

    public function testAnotherKeyCannotRead(): void
    {
        $envelope = (new SecretCipher(self::KEY))->encrypt('JBSWY3DPEHPK3PXP');

        $this->expectException(\RuntimeException::class);

        (new SecretCipher('a-completely-different-key'))->decrypt($envelope);
    }

    public function testTamperingIsDetected(): void
    {
        // The property CBC does not have. Flip one byte of ciphertext and decryption must
        // fail rather than return a different secret.
        $cipher = new SecretCipher(self::KEY);
        $envelope = $cipher->encrypt('JBSWY3DPEHPK3PXP');

        [$version, $iv, $tag, $ciphertext] = explode('.', $envelope);
        $bytes = base64_decode(strtr($ciphertext, '-_', '+/'), true);
        $bytes[0] = $bytes[0] === "\x00" ? "\x01" : "\x00";
        $tampered = implode('.', [$version, $iv, $tag, rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=')]);

        $this->expectException(\RuntimeException::class);

        $cipher->decrypt($tampered);
    }

    public function testAReplacedTagIsDetected(): void
    {
        $cipher = new SecretCipher(self::KEY);
        [$version, $iv, , $ciphertext] = explode('.', $cipher->encrypt('JBSWY3DPEHPK3PXP'));
        $forged = rtrim(strtr(base64_encode(str_repeat("\x00", 16)), '+/', '-_'), '=');

        $this->expectException(\RuntimeException::class);

        $cipher->decrypt(implode('.', [$version, $iv, $forged, $ciphertext]));
    }

    public function testAMalformedEnvelopeIsRefusedRatherThanGuessedAt(): void
    {
        $cipher = new SecretCipher(self::KEY);

        foreach (['', 'not-an-envelope', 'v1.only.three', 'v2.a.b.c'] as $garbage) {
            try {
                $cipher->decrypt($garbage);
                self::fail(sprintf('"%s" should not have decrypted', $garbage));
            } catch (\RuntimeException) {
                self::assertTrue(true);
            }
        }
    }

    public function testAWeakKeyIsRejectedAtConstruction(): void
    {
        // Not a style preference: OpenSSL would silently pad a short key to 32 bytes, so a
        // memorable config string would become a weak key with no sign that it had.
        $this->expectException(\InvalidArgumentException::class);

        new SecretCipher('short');
    }

    public function testTheEnvelopeIsVersioned(): void
    {
        // So a future key rotation or cipher change is recognisable rather than a decryption
        // failure of unknown cause.
        self::assertStringStartsWith('v1.', (new SecretCipher(self::KEY))->encrypt('JBSWY3DPEHPK3PXP'));
    }
}
