<?php

declare(strict_types=1);

namespace Skeletor\Tests\Core\Security;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Skeletor\Core\Config\Config;
use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Core\Security\AuthPolicy;
use Skeletor\Tests\Support\TestAccount;

/**
 * The one place that answers "what ways in does this application have".
 *
 * Two properties carry the weight. Config has to fail loudly rather than quietly disabling a
 * login method, because the symptom of a quiet failure is "the login page 404s" discovered
 * long after the deploy. And an entity must never be able to widen what config allows, since
 * supportsAuthenticator() lives on entity classes, which is where an oversight is least
 * visible.
 */
#[CoversClass(AuthPolicy::class)]
final class AuthPolicyTest extends TestCase
{
    private function policy(array $auth): AuthPolicy
    {
        return new AuthPolicy(new Config(['auth' => $auth]));
    }

    // ---- defaults ---------------------------------------------------------------------

    public function testAnApplicationWithNoAuthConfigKeepsThePreviousBehaviour(): void
    {
        // What the framework did before the node existed. An app upgrading must not have its
        // login change shape until it says so.
        $policy = new AuthPolicy(new Config([]));

        self::assertSame([AuthPolicy::METHOD_PASSWORD, AuthPolicy::METHOD_MAGIC_LINK], $policy->methods());
        self::assertSame(AuthPolicy::METHOD_PASSWORD, $policy->defaultMethod());
        self::assertFalse($policy->twoFactorRequired());
    }

    public function testAnEmptyMethodsListIsTreatedAsUnconfiguredRatherThanAsNoWayIn(): void
    {
        // An app that locks everybody out is never what was meant.
        $policy = $this->policy(['methods' => []]);

        self::assertSame([AuthPolicy::METHOD_PASSWORD, AuthPolicy::METHOD_MAGIC_LINK], $policy->methods());
    }

    public function testTheDefaultMethodFallsBackToTheFirstEnabledOne(): void
    {
        $policy = $this->policy(['methods' => [AuthPolicy::METHOD_MAGIC_LINK, AuthPolicy::METHOD_PASSWORD]]);

        self::assertSame(AuthPolicy::METHOD_MAGIC_LINK, $policy->defaultMethod());
    }

    // ---- bad config -------------------------------------------------------------------

    public function testAnUnknownMethodNameIsRefusedAtConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->policy(['methods' => ['magic-link']]);
    }

    public function testADefaultThatIsNotEnabledIsRefusedAtConstruction(): void
    {
        // Otherwise the front door points at a method whose endpoints 404.
        $this->expectException(\InvalidArgumentException::class);

        $this->policy(['methods' => [AuthPolicy::METHOD_MAGIC_LINK], 'default' => AuthPolicy::METHOD_PASSWORD]);
    }

    // ---- the switch -------------------------------------------------------------------

    public function testOnlyConfiguredMethodsAreEnabled(): void
    {
        $policy = $this->policy(['methods' => [AuthPolicy::METHOD_MAGIC_LINK]]);

        self::assertTrue($policy->isEnabled(AuthPolicy::METHOD_MAGIC_LINK));
        self::assertFalse($policy->isEnabled(AuthPolicy::METHOD_PASSWORD));
        self::assertFalse($policy->isEnabled(AuthPolicy::METHOD_SSO));
    }

    public function testAssertingADisabledMethodIsANotFound(): void
    {
        $policy = $this->policy(['methods' => [AuthPolicy::METHOD_MAGIC_LINK]]);

        $policy->assertEnabled(AuthPolicy::METHOD_MAGIC_LINK);

        $this->expectException(NotFoundException::class);
        $policy->assertEnabled(AuthPolicy::METHOD_PASSWORD);
    }

    public function testTwoFactorIsAWholeApplicationSwitch(): void
    {
        self::assertTrue($this->policy(['methods' => ['password'], 'twoFactor' => true])->twoFactorRequired());
        self::assertFalse($this->policy(['methods' => ['password'], 'twoFactor' => false])->twoFactorRequired());
        self::assertFalse($this->policy(['methods' => ['password']])->twoFactorRequired());
    }

    // ---- the account's say --------------------------------------------------------------

    public function testAnAccountCanNarrowWhatItUsesButNotWidenIt(): void
    {
        // TestAccount supports password and magic link, not sso.
        $everything = $this->policy(['methods' => AuthPolicy::METHODS, 'default' => AuthPolicy::METHOD_PASSWORD]);
        $account = new TestAccount();

        self::assertTrue($everything->availableTo(AuthPolicy::METHOD_PASSWORD, $account));
        self::assertFalse(
            $everything->availableTo(AuthPolicy::METHOD_SSO, $account),
            'the account does not support sso, so enabling it application-wide must not matter'
        );

        $magicLinkOnly = $this->policy(['methods' => [AuthPolicy::METHOD_MAGIC_LINK]]);
        self::assertFalse(
            $magicLinkOnly->availableTo(AuthPolicy::METHOD_PASSWORD, $account),
            'the account supports passwords, but config does not offer them'
        );
    }

    // ---- the front door -----------------------------------------------------------------

    public function testTheLoginPathFollowsTheDefaultMethodAndTheEntityType(): void
    {
        self::assertSame(
            '/login/user/loginForm/',
            $this->policy(['methods' => [AuthPolicy::METHOD_PASSWORD]])->loginPath('user')
        );
        self::assertSame(
            '/login/delegate/magicLinkForm/',
            $this->policy(['methods' => [AuthPolicy::METHOD_MAGIC_LINK]])->loginPath('delegate')
        );
        self::assertSame(
            '/login/user/sso/',
            $this->policy(['methods' => [AuthPolicy::METHOD_SSO]])->loginPath('user')
        );
    }
}
