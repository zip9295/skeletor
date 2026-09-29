<?php

declare(strict_types=1);

namespace Skeletor\Tests\Core\Login\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Skeletor\Core\Config\Config;
use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Core\Security\EntityRegistry;
use Skeletor\Core\Login\Exception\InvalidCredentials;
use Skeletor\Core\Login\Exception\MagicLinkThrottled;
use Skeletor\Core\Login\Repository\MagicLinkTokenRepository;
use Skeletor\Core\Login\Service\MagicLinkService;
use Skeletor\Core\Login\Service\TokenGenerator;
use Skeletor\Tests\Support\InMemoryAccountRepository;
use Skeletor\Tests\Support\InMemoryMagicLinkTokenRepository;
use Skeletor\Tests\Support\RecordingMailer;
use Skeletor\Tests\Support\TestAccount;

/**
 * Issuing a login link.
 *
 * A magic link hands out a session directly, so every question about whether an account may
 * log in has to be answered before the token is minted rather than when it is followed. The
 * gates below used to live in one app's login controller and not in another's.
 */
#[CoversClass(MagicLinkService::class)]
final class MagicLinkServiceTest extends TestCase
{
    private InMemoryMagicLinkTokenRepository $tokens;

    private RecordingMailer $mailer;

    private EntityRegistry $registry;

    private TestAccount $account;

    protected function setUp(): void
    {
        $this->tokens = new InMemoryMagicLinkTokenRepository();
        $this->mailer = new RecordingMailer();
        $this->account = new TestAccount(id: 12, email: 'someone@example.com');
        $this->registry = new EntityRegistry();
        $this->registry->register('user', TestAccount::class, new InMemoryAccountRepository(true, $this->account));
    }

    private function service(array $config = []): MagicLinkService
    {
        return new MagicLinkService(
            new TokenGenerator(),
            $this->tokens,
            $this->registry,
            $this->mailer,
            new Config($config + ['adminUrl' => 'https://admin.example.com', 'adminPath' => '']),
        );
    }

    public function testIssuesATokenAndMailsIt(): void
    {
        $token = $this->service()->requestMagicLink('someone@example.com', 'user');

        self::assertNotSame('', $token);
        self::assertCount(1, $this->mailer->magicLinks);
        self::assertSame('someone@example.com', $this->mailer->magicLinks[0]['email']);
        self::assertStringContainsString($token, $this->mailer->lastMagicLinkUrl());
    }

    public function testTheTokenIsStoredHashedRatherThanInTheClear(): void
    {
        // A magic link is a bearer credential. A leak of this table — a backup, a replica, a
        // read-only injection — must not be a way into every account with a live link.
        $token = $this->service()->requestMagicLink('someone@example.com', 'user');

        self::assertArrayNotHasKey($token, $this->tokens->tokens);
        self::assertArrayHasKey(MagicLinkTokenRepository::hash($token), $this->tokens->tokens);
        self::assertNotNull($this->tokens->findByToken($token), 'and it still resolves by plaintext');
    }

    public function testAnInactiveAccountIsRefusedBeforeATokenExists(): void
    {
        // The gate that one app had and the other did not: an unapproved delegate must not be
        // able to mail themselves a working key to the dashboard.
        $this->account->deactivate();

        try {
            $this->service()->requestMagicLink('someone@example.com', 'user');
            self::fail('an inactive account should not be sent a link');
        } catch (InvalidCredentials) {
            self::assertSame([], $this->tokens->tokens, 'no token may be minted');
            self::assertSame([], $this->mailer->magicLinks, 'and nothing may be sent');
        }
    }

    public function testAnUnknownAddressIsReportedAndMintsNothing(): void
    {
        try {
            $this->service()->requestMagicLink('nobody@example.com', 'user');
            self::fail('an unknown address should not be sent a link');
        } catch (NotFoundException) {
            self::assertSame([], $this->tokens->tokens);
        }
    }

    public function testAskingAgainInvalidatesTheLinkAlreadyInFlight(): void
    {
        // So a forwarded or logged older email cannot be used behind the account holder.
        $service = $this->service();
        $first = $service->requestMagicLink('someone@example.com', 'user');
        $service->requestMagicLink('someone@example.com', 'user');

        self::assertFalse($this->tokens->findByToken($first)->isUsable());
    }

    public function testCallersThatSendTheirOwnMailGetTheTokenAndNoEmail(): void
    {
        // The frontend donor flow: same token, an app-authored letter with a public URL.
        $token = $this->service()->requestMagicLink('someone@example.com', 'user', sendEmail: false);

        self::assertNotSame('', $token);
        self::assertSame([], $this->mailer->magicLinks);
    }

    public function testRememberMeIsCarriedOnTheTokenToBeReadAfterTheLinkIsFollowed(): void
    {
        $service = $this->service();
        $token = $service->requestMagicLink('someone@example.com', 'user', rememberMe: true);

        self::assertTrue($service->shouldRemember($token));
    }

    public function testRememberMeDefaultsToOff(): void
    {
        $service = $this->service();

        self::assertFalse($service->shouldRemember($service->requestMagicLink('someone@example.com', 'user')));
    }

    public function testPeekingAtALinkDoesNotSpendIt(): void
    {
        // The confirm page checks the token before rendering a button for it; only following
        // through may consume it.
        $service = $this->service();
        $token = $service->requestMagicLink('someone@example.com', 'user');

        $service->peek($token);

        self::assertTrue($this->tokens->findByToken($token)->isUsable());
    }

    public function testPeekingAtASpentLinkSaysSo(): void
    {
        $service = $this->service();
        $token = $service->requestMagicLink('someone@example.com', 'user');
        $this->tokens->findByToken($token)->markAsUsed();

        $this->expectException(InvalidCredentials::class);
        $service->peek($token);
    }

    // ---- cooldown -------------------------------------------------------------------

    public function testWithNoCooldownConfiguredNothingIsThrottled(): void
    {
        // The default has to stay off: an app upgrading the framework must not start
        // refusing its users' second attempt without having asked for that.
        $service = $this->service();
        $service->requestMagicLink('someone@example.com', 'user');

        $this->expectNotToPerformAssertions();
        $service->requestMagicLink('someone@example.com', 'user');
    }

    public function testASecondRequestInsideTheCooldownIsRefused(): void
    {
        // Each request invalidates the last, so an unthrottled endpoint lets anyone keep a
        // real user's link permanently broken while filling their inbox.
        $service = $this->service(['magicLink' => ['cooldownSeconds' => 60]]);
        $service->requestMagicLink('someone@example.com', 'user');

        $this->expectException(MagicLinkThrottled::class);
        $service->requestMagicLink('someone@example.com', 'user');
    }

    public function testAThrottledRequestLeavesTheExistingLinkAlone(): void
    {
        // Refusing must not invalidate the link the user is already holding — that would
        // make the throttle itself the denial of service.
        $service = $this->service(['magicLink' => ['cooldownSeconds' => 60]]);
        $token = $service->requestMagicLink('someone@example.com', 'user');

        try {
            $service->requestMagicLink('someone@example.com', 'user');
        } catch (MagicLinkThrottled) {
            // expected
        }

        self::assertTrue($this->tokens->findByToken($token)->isUsable());
        self::assertCount(1, $this->mailer->magicLinks);
    }

    public function testTheCooldownIsPerAccount(): void
    {
        $this->registry->register(
            'delegate',
            TestAccount::class,
            new InMemoryAccountRepository(true, new TestAccount(id: 99, email: 'other@example.com'))
        );
        $service = $this->service(['magicLink' => ['cooldownSeconds' => 60]]);
        $service->requestMagicLink('someone@example.com', 'user');

        $this->expectNotToPerformAssertions();
        $service->requestMagicLink('other@example.com', 'delegate');
    }

    // ---- where the link points ------------------------------------------------------

    public function testTheDefaultLinkPointsAtTheFrameworkAdminRoute(): void
    {
        $token = $this->service()->requestMagicLink('someone@example.com', 'user');

        self::assertSame(
            sprintf('https://admin.example.com/login/user/verifyMagicLink/%s/', $token),
            $this->mailer->lastMagicLinkUrl()
        );
    }

    public function testTheDefaultLinkHonoursAConfiguredAdminPath(): void
    {
        $token = $this->service(['adminPath' => 'secret'])->requestMagicLink('someone@example.com', 'user');

        self::assertSame(
            sprintf('https://admin.example.com/secret/login/user/verifyMagicLink/%s/', $token),
            $this->mailer->lastMagicLinkUrl()
        );
    }

    public function testTheLinkCarriesTheEntityTypeItWasIssuedFor(): void
    {
        $this->registry->register(
            'delegate',
            TestAccount::class,
            new InMemoryAccountRepository(true, new TestAccount(id: 21, email: 'del@example.com'))
        );

        $this->service()->requestMagicLink('del@example.com', 'delegate');

        self::assertStringContainsString('/login/delegate/verifyMagicLink/', $this->mailer->lastMagicLinkUrl());
    }

    public function testAnEntityTypeCanPointItsLinkAtTheFrontend(): void
    {
        // A donor follows their link into the public site, not the admin. This was hardcoded
        // to adminUrl, which is why the app had to send that email itself.
        $service = $this->service([
            'baseUrl' => 'https://www.example.com',
            'magicLink' => ['urls' => ['user' => '{baseUrl}/donor/verifyEmail?token={token}']],
        ]);

        $token = $service->requestMagicLink('someone@example.com', 'user');

        self::assertSame(
            sprintf('https://www.example.com/donor/verifyEmail?token=%s', $token),
            $this->mailer->lastMagicLinkUrl()
        );
    }

    public function testTheLinkLifetimeIsConfigurable(): void
    {
        $service = $this->service(['magicLink' => ['expiryMinutes' => 5]]);
        $token = $service->requestMagicLink('someone@example.com', 'user');

        $stored = $this->tokens->findByToken($token);
        $lifetime = $stored->expiresAt->getTimestamp() - $stored->createdAt->getTimestamp();

        self::assertSame(300, $lifetime);
    }

    public function testTheDefaultLifetimeIsFifteenMinutes(): void
    {
        $token = $this->service()->requestMagicLink('someone@example.com', 'user');
        $stored = $this->tokens->findByToken($token);

        self::assertSame(900, $stored->expiresAt->getTimestamp() - $stored->createdAt->getTimestamp());
    }
}
