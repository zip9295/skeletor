<?php

declare(strict_types=1);

namespace Skeletor\Tests\Core\Security\TwoFactor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Skeletor\Core\Security\TwoFactor\Base32;
use Skeletor\Core\Security\TwoFactor\TotpGenerator;

/**
 * The acceptance test for a hand-written TOTP.
 *
 * Every case in the first provider is verbatim from RFC 6238 Appendix B, across all three
 * HMAC variants. That is what justifies not taking a dependency here: the algorithm has an
 * official answer sheet, and this file is it. If this class is ever touched, these are the
 * tests that say whether it still works — not the ones about windows and formats below.
 */
#[CoversClass(TotpGenerator::class)]
final class TotpGeneratorTest extends TestCase
{
    private const SEED_SHA1 = '12345678901234567890';
    private const SEED_SHA256 = '12345678901234567890123456789012';
    private const SEED_SHA512 = '1234567890123456789012345678901234567890123456789012345678901234';

    /** @return list<array{string, string, int, string}> */
    public static function rfc6238Vectors(): array
    {
        return [
            [TotpGenerator::ALGORITHM_SHA1, self::SEED_SHA1, 59, '94287082'],
            [TotpGenerator::ALGORITHM_SHA1, self::SEED_SHA1, 1111111109, '07081804'],
            [TotpGenerator::ALGORITHM_SHA1, self::SEED_SHA1, 1111111111, '14050471'],
            [TotpGenerator::ALGORITHM_SHA1, self::SEED_SHA1, 1234567890, '89005924'],
            [TotpGenerator::ALGORITHM_SHA1, self::SEED_SHA1, 2000000000, '69279037'],
            [TotpGenerator::ALGORITHM_SHA1, self::SEED_SHA1, 20000000000, '65353130'],

            [TotpGenerator::ALGORITHM_SHA256, self::SEED_SHA256, 59, '46119246'],
            [TotpGenerator::ALGORITHM_SHA256, self::SEED_SHA256, 1111111109, '68084774'],
            [TotpGenerator::ALGORITHM_SHA256, self::SEED_SHA256, 1111111111, '67062674'],
            [TotpGenerator::ALGORITHM_SHA256, self::SEED_SHA256, 1234567890, '91819424'],
            [TotpGenerator::ALGORITHM_SHA256, self::SEED_SHA256, 2000000000, '90698825'],
            [TotpGenerator::ALGORITHM_SHA256, self::SEED_SHA256, 20000000000, '77737706'],

            [TotpGenerator::ALGORITHM_SHA512, self::SEED_SHA512, 59, '90693936'],
            [TotpGenerator::ALGORITHM_SHA512, self::SEED_SHA512, 1111111109, '25091201'],
            [TotpGenerator::ALGORITHM_SHA512, self::SEED_SHA512, 1111111111, '99943326'],
            [TotpGenerator::ALGORITHM_SHA512, self::SEED_SHA512, 1234567890, '93441116'],
            [TotpGenerator::ALGORITHM_SHA512, self::SEED_SHA512, 2000000000, '38618901'],
            [TotpGenerator::ALGORITHM_SHA512, self::SEED_SHA512, 20000000000, '47863826'],
        ];
    }

    #[DataProvider('rfc6238Vectors')]
    public function testMatchesTheRfcTestVectors(string $algorithm, string $seed, int $time, string $expected): void
    {
        $totp = new TotpGenerator(digits: 8, period: 30, algorithm: $algorithm);

        self::assertSame($expected, $totp->at(Base32::encode($seed), $time));
    }

    #[DataProvider('rfc6238Vectors')]
    public function testAcceptsTheRfcTestVectors(string $algorithm, string $seed, int $time, string $expected): void
    {
        $totp = new TotpGenerator(digits: 8, period: 30, algorithm: $algorithm);

        self::assertTrue($totp->verify(Base32::encode($seed), $expected, $time, window: 0));
    }

    public function testAcceptsTheNeighbouringPeriodsButNotTheOneBeyond(): void
    {
        // A phone whose clock is a few seconds out is the normal case, not the exception;
        // rejecting it makes two-factor look broken. An unbounded window makes the code valid
        // long after the user can still see it.
        $totp = new TotpGenerator();
        $secret = Base32::encode(self::SEED_SHA1);
        $now = 1111111111;

        self::assertTrue($totp->verify($secret, $totp->at($secret, $now - 30), $now, window: 1));
        self::assertTrue($totp->verify($secret, $totp->at($secret, $now + 30), $now, window: 1));
        self::assertFalse($totp->verify($secret, $totp->at($secret, $now - 60), $now, window: 1));
        self::assertFalse($totp->verify($secret, $totp->at($secret, $now + 60), $now, window: 1));
    }

    public function testReportsWhichPeriodMatched(): void
    {
        // The counter, not just the yes/no, is what makes replay detectable — see
        // TwoFactorSecret::hasSpent().
        $totp = new TotpGenerator();
        $secret = Base32::encode(self::SEED_SHA1);
        $now = 1111111111;

        self::assertSame(intdiv($now - 30, 30), $totp->matchCounter($secret, $totp->at($secret, $now - 30), $now));
        self::assertNull($totp->matchCounter($secret, '000000', $now));
    }

    public function testCodeStaysStableForTheWholePeriodAndChangesAfterIt(): void
    {
        $totp = new TotpGenerator();
        $secret = Base32::encode(self::SEED_SHA1);

        self::assertSame($totp->at($secret, 1111111110), $totp->at($secret, 1111111139));
        self::assertNotSame($totp->at($secret, 1111111110), $totp->at($secret, 1111111140));
    }

    public function testCodesAreZeroPaddedToTheConfiguredLength(): void
    {
        // '07081804' in the RFC vectors is the reason: dropping the leading zero makes a
        // seven-character code that never matches what the phone shows.
        $totp = new TotpGenerator(digits: 8);

        self::assertSame('07081804', $totp->at(Base32::encode(self::SEED_SHA1), 1111111109));
    }

    /** @return list<array{string}> */
    public static function malformedCodes(): array
    {
        return [[''], ['12345'], ['1234567'], ['abcdef'], ['0x1234']];
    }

    #[DataProvider('malformedCodes')]
    public function testRejectsAnythingThatIsNotACodeOfTheRightLength(string $code): void
    {
        $totp = new TotpGenerator();

        self::assertFalse($totp->verify(Base32::encode(self::SEED_SHA1), $code, 1111111111));
    }

    public function testStripsSpacesFromAPastedCode(): void
    {
        // Authenticator apps display "123 456" and people paste it that way.
        $totp = new TotpGenerator();
        $secret = Base32::encode(self::SEED_SHA1);
        $code = $totp->at($secret, 1111111111);

        self::assertTrue($totp->verify($secret, substr($code, 0, 3) . ' ' . substr($code, 3), 1111111111));
    }

    public function testGeneratedSecretsAreBase32AndDistinct(): void
    {
        $totp = new TotpGenerator();

        $first = $totp->generateSecret();
        $second = $totp->generateSecret();

        self::assertNotSame($first, $second);
        self::assertSame(20, strlen(Base32::decode($first)));
        self::assertMatchesRegularExpression('/^[A-Z2-7]+$/', $first);
    }

    public function testRefusesASecretShorterThanTheRfcRecommends(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new TotpGenerator())->generateSecret(8);
    }

    public function testProvisioningUriCarriesEverythingAnAppNeeds(): void
    {
        $totp = new TotpGenerator(digits: 6, period: 30);
        $uri = $totp->getProvisioningUri('JBSWY3DPEHPK3PXP', 'someone@example.com', 'Skeletor Admin');

        self::assertStringStartsWith('otpauth://totp/Skeletor%20Admin:someone%40example.com?', $uri);
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $params);
        self::assertSame('JBSWY3DPEHPK3PXP', $params['secret']);
        self::assertSame('Skeletor Admin', $params['issuer']);
        self::assertSame('SHA1', $params['algorithm']);
        self::assertSame('6', $params['digits']);
        self::assertSame('30', $params['period']);
    }

    public function testRefusesNonsenseConfiguration(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TotpGenerator(digits: 4);
    }
}
