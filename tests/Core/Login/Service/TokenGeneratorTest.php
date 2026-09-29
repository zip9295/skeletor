<?php

declare(strict_types=1);

namespace Skeletor\Tests\Core\Login\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Skeletor\Core\Login\Service\TokenGenerator;

/**
 * Short, but the one place where "it looks random" is not good enough: these tokens are the
 * whole credential in a magic link.
 */
#[CoversClass(TokenGenerator::class)]
final class TokenGeneratorTest extends TestCase
{
    public function testTokensAreHexOfTheRequestedByteLength(): void
    {
        $token = (new TokenGenerator())->generate(64);

        self::assertSame(128, strlen($token));
        self::assertMatchesRegularExpression('/^[0-9a-f]+$/', $token);
    }

    public function testTokensDoNotRepeat(): void
    {
        $generator = new TokenGenerator();
        $tokens = [];
        for ($i = 0; $i < 200; $i++) {
            $tokens[] = $generator->generate(32);
        }

        self::assertCount(200, array_unique($tokens));
    }

    public function testUrlSafeTokensSurviveBeingPutInALink(): void
    {
        $token = (new TokenGenerator())->generateUrlSafe(32);

        self::assertSame($token, rawurlencode($token));
    }

    public function testTheHashedFormIsDerivedFromTheTokenAndNotStoredWithIt(): void
    {
        [$token, $hash] = (new TokenGenerator())->generateWithHash(32);

        self::assertSame(hash('sha256', $token), $hash);
        self::assertNotSame($token, $hash);
    }
}
