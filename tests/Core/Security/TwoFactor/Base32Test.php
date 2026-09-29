<?php

declare(strict_types=1);

namespace Skeletor\Tests\Core\Security\TwoFactor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Skeletor\Core\Security\TwoFactor\Base32;

/**
 * Pinned against RFC 4648 section 10, which is the whole specification of this class.
 *
 * Worth having despite being twenty lines of table lookup: a base32 bug does not announce
 * itself, it produces a secret that differs from the one in the user's phone, and the
 * symptom is "the code is always wrong" forever rather than an error anyone can trace.
 */
#[CoversClass(Base32::class)]
final class Base32Test extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function rfc4648Vectors(): array
    {
        return [
            'empty' => ['', ''],
            'f' => ['f', 'MY'],
            'fo' => ['fo', 'MZXQ'],
            'foo' => ['foo', 'MZXW6'],
            'foob' => ['foob', 'MZXW6YQ'],
            'fooba' => ['fooba', 'MZXW6YTB'],
            'foobar' => ['foobar', 'MZXW6YTBOI'],
        ];
    }

    #[DataProvider('rfc4648Vectors')]
    public function testEncodesTheRfcVectors(string $plain, string $encoded): void
    {
        self::assertSame($encoded, Base32::encode($plain));
    }

    #[DataProvider('rfc4648Vectors')]
    public function testDecodesTheRfcVectors(string $plain, string $encoded): void
    {
        self::assertSame($plain, Base32::decode($encoded));
    }

    public function testPaddingIsOptionalOnTheWayOutAndIgnoredOnTheWayIn(): void
    {
        // Authenticator apps show secrets unpadded and users retype them that way, so both
        // forms have to decode to the same bytes.
        self::assertSame('MZXW6YTBOI======', Base32::encode('foobar', true));
        self::assertSame('foobar', Base32::decode('MZXW6YTBOI======'));
        self::assertSame('foobar', Base32::decode('MZXW6YTBOI'));
    }

    public function testWhitespaceAndCaseSurviveRetyping(): void
    {
        // The setup page prints the secret in groups of four; someone typing it back adds
        // spaces and may not hold shift.
        self::assertSame('foobar', Base32::decode('mzxw 6ytb oi'));
    }

    public function testAnUnknownCharacterIsRefusedRatherThanSkipped(): void
    {
        // Skipping it would silently yield a different secret, which fails as "wrong code"
        // every thirty seconds forever instead of as "you pasted that wrong" once.
        $this->expectException(\InvalidArgumentException::class);

        Base32::decode('MZXW6YTB1I');
    }

    public function testRoundTripsArbitraryBytes(): void
    {
        foreach ([1, 5, 10, 16, 20, 32, 64] as $length) {
            $raw = random_bytes($length);
            self::assertSame($raw, Base32::decode(Base32::encode($raw)), "length {$length}");
        }
    }
}
