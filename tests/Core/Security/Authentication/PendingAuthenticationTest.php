<?php

declare(strict_types=1);

namespace Skeletor\Tests\Core\Security\Authentication;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Skeletor\Core\Security\Authentication\PendingAuthentication;
use Skeletor\Tests\Support\RecordingSessionManager;

/**
 * The half-authenticated state, which exists so the second factor cannot be told which
 * account it is checking.
 *
 * The behaviour worth pinning is negative: nothing here is readable from a request, a stale
 * challenge cannot be completed, and beginning a challenge does not log anybody in.
 */
#[CoversClass(PendingAuthentication::class)]
final class PendingAuthenticationTest extends TestCase
{
    private RecordingSessionManager $session;

    protected function setUp(): void
    {
        $this->session = new RecordingSessionManager();
    }

    public function testNothingIsPendingToBeginWith(): void
    {
        $pending = new PendingAuthentication($this->session);

        self::assertFalse($pending->isPending());
        self::assertNull($pending->getEntityType());
        self::assertNull($pending->getEntityId());
    }

    public function testBeginningAChallengeRecordsWhoAndHow(): void
    {
        $pending = new PendingAuthentication($this->session);

        $pending->begin('delegate', 42, true, '/beneficiary/view/');

        self::assertTrue($pending->isPending());
        self::assertSame('delegate', $pending->getEntityType());
        self::assertSame(42, $pending->getEntityId());
        self::assertTrue($pending->shouldRememberMe());
        self::assertSame('/beneficiary/view/', $pending->getRedirectPath());
    }

    public function testBeginningAChallengeDoesNotLogAnybodyIn(): void
    {
        // The one thing that must never happen here. AuthMiddleware only checks that
        // 'loggedIn' is truthy, so writing it before the second factor would make the second
        // factor decorative.
        $pending = new PendingAuthentication($this->session);

        $pending->begin('user', 1);

        self::assertNull($this->session->getStorage()->offsetGet('loggedIn'));
    }

    public function testAnExpiredChallengeIsGoneAndCannotBeCompleted(): void
    {
        // An abandoned login on a shared machine should not stay open until the cookie dies.
        $pending = new PendingAuthentication($this->session, ttlSeconds: -1);

        $pending->begin('user', 1);

        self::assertFalse($pending->isPending());
        self::assertNull($pending->getEntityType());
    }

    public function testAnExpiredChallengeIsClearedFromTheSessionOnRead(): void
    {
        $pending = new PendingAuthentication($this->session, ttlSeconds: -1);
        $pending->begin('user', 1);

        $pending->isPending();

        self::assertNull($this->session->getStorage()->offsetGet(PendingAuthentication::SESSION_KEY));
    }

    public function testGarbageInTheSessionKeyIsNotTreatedAsAChallenge(): void
    {
        $this->session->getStorage()->offsetSet(PendingAuthentication::SESSION_KEY, ['entityType' => 'user']);

        self::assertFalse((new PendingAuthentication($this->session))->isPending());
    }

    public function testTheIssuedEnrolmentSecretSurvivesARerender(): void
    {
        // So refreshing the setup page shows the QR the user is halfway through scanning
        // rather than a freshly rotated one.
        $pending = new PendingAuthentication($this->session);
        $pending->begin('user', 1);

        $pending->rememberEnrolmentSecret('JBSWY3DPEHPK3PXP');

        self::assertSame('JBSWY3DPEHPK3PXP', $pending->getEnrolmentSecret());
        self::assertSame('user', $pending->getEntityType(), 'holding the secret must not disturb the rest');
    }

    public function testAnEnrolmentSecretIsNotKeptWithoutAChallengeToHoldIt(): void
    {
        $pending = new PendingAuthentication($this->session);

        $pending->rememberEnrolmentSecret('JBSWY3DPEHPK3PXP');

        self::assertNull($pending->getEnrolmentSecret());
    }

    public function testClearingEndsTheChallengeAndTakesTheSecretWithIt(): void
    {
        $pending = new PendingAuthentication($this->session);
        $pending->begin('user', 1);
        $pending->rememberEnrolmentSecret('JBSWY3DPEHPK3PXP');

        $pending->clear();

        self::assertFalse($pending->isPending());
        self::assertNull($pending->getEnrolmentSecret());
    }

    public function testARestartedChallengeReplacesTheOneBefore(): void
    {
        $pending = new PendingAuthentication($this->session);
        $pending->begin('user', 1, true, '/a/');

        $pending->begin('delegate', 2);

        self::assertSame('delegate', $pending->getEntityType());
        self::assertSame(2, $pending->getEntityId());
        self::assertFalse($pending->shouldRememberMe());
        self::assertNull($pending->getRedirectPath());
    }
}
