<?php

declare(strict_types=1);

namespace Skeletor\Tests\Core\Security\TwoFactor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Skeletor\Core\Security\TwoFactor\SecretCipher;
use Skeletor\Core\Security\TwoFactor\TotpGenerator;
use Skeletor\Core\Security\TwoFactor\TwoFactorService;
use Skeletor\Core\Login\Exception\InvalidCredentials;
use Skeletor\Core\Login\Exception\TwoFactorLockedOut;
use Skeletor\Core\Security\AuthPolicy;
use Skeletor\Tests\Support\InMemoryTwoFactorSecretRepository;
use Skeletor\Tests\Support\Policies;
use Skeletor\Tests\Support\TestAccount;

/**
 * Policy, not arithmetic: TotpGeneratorTest already proves a code is right or wrong. What
 * matters here is what the service does about it — that a half-finished enrolment does not
 * lock anyone out, that guessing is bounded, and that a correct code cannot be used twice.
 */
#[CoversClass(TwoFactorService::class)]
final class TwoFactorServiceTest extends TestCase
{
    private const KEY = 'two-factor-encryption-key-for-tests';
    private const TYPE = 'user';

    private InMemoryTwoFactorSecretRepository $repository;

    private \DateTime $now;

    private TotpGenerator $totp;

    protected function setUp(): void
    {
        $this->now = new \DateTime('@1111111111');
        $this->repository = new InMemoryTwoFactorSecretRepository($this->now);
        $this->totp = new TotpGenerator();
    }

    private function service(bool $twoFactor = true, int $maxAttempts = 3): TwoFactorService
    {
        return new TwoFactorService(
            $this->repository,
            $this->totp,
            new SecretCipher(self::KEY),
            $this->now,
            Policies::with([AuthPolicy::METHOD_PASSWORD], AuthPolicy::METHOD_PASSWORD, $twoFactor),
            'Skeletor',
            maxAttempts: $maxAttempts,
            lockSeconds: 300,
        );
    }

    /** The same service, but with its clock somewhere else. */
    private function serviceAt(\DateTime $when, int $maxAttempts = 3): TwoFactorService
    {
        return new TwoFactorService(
            $this->repository,
            $this->totp,
            new SecretCipher(self::KEY),
            $when,
            Policies::with([AuthPolicy::METHOD_PASSWORD], AuthPolicy::METHOD_PASSWORD, true),
            'Skeletor',
            maxAttempts: $maxAttempts,
            lockSeconds: 300,
        );
    }

    private function codeFor(string $secret, int $offsetSeconds = 0): string
    {
        return $this->totp->at($secret, $this->now->getTimestamp() + $offsetSeconds);
    }

    // ---- who owes a second factor -------------------------------------------------

    public function testTheApplicationSwitchDecidesWhetherASecondFactorIsOwed(): void
    {
        self::assertTrue($this->service(twoFactor: true)->isRequiredFor(new TestAccount()));
        self::assertFalse($this->service(twoFactor: false)->isRequiredFor(new TestAccount()));
    }

    public function testAnEntityMayOptItselfOutButOnlyWhileTheApplicationHasItOn(): void
    {
        // Narrowing only, in the same direction as AuthPolicy::availableTo(): a donor on a
        // public site should not be dragged through an authenticator app because the staff
        // dashboard requires one, but an entity class must not be able to switch two-factor
        // back on for an application that has it off.
        $optedOut = (new TestAccount())->requireTwoFactor(false);
        $optedIn = (new TestAccount())->requireTwoFactor();

        self::assertFalse($this->service(twoFactor: true)->isRequiredFor($optedOut));
        self::assertTrue($this->service(twoFactor: true)->isRequiredFor($optedIn));
        self::assertFalse($this->service(twoFactor: false)->isRequiredFor($optedIn), 'config wins');
    }

    public function testNothingIsRequiredWhenTheApplicationHasItOff(): void
    {
        // The shape solidarity ships in: the code is present and switched off, and its
        // presence must not change any login.
        self::assertFalse($this->service(twoFactor: false)->isRequiredFor(new TestAccount()));
    }

    // ---- enrolment ------------------------------------------------------------------

    public function testEnrolmentStoresTheSecretEncryptedAndUnconfirmed(): void
    {
        $service = $this->service();
        $enrolment = $service->beginEnrolment(self::TYPE, new TestAccount(id: 7));

        $record = $this->repository->find(self::TYPE, 7);
        self::assertNotNull($record);
        self::assertStringNotContainsString($enrolment->secret, $record->secret, 'secret must not be at rest in the clear');
        self::assertFalse($record->isConfirmed());
    }

    public function testAnUnconfirmedEnrolmentDoesNotCountAsEnrolled(): void
    {
        // The whole reason confirmedAt exists. If merely opening the setup page counted, a
        // user who closed the tab would be locked out of their own account by a secret that
        // exists nowhere else.
        $service = $this->service();
        $service->beginEnrolment(self::TYPE, new TestAccount(id: 7));

        self::assertFalse($service->isEnrolled(self::TYPE, 7));
    }

    public function testConfirmingWithACorrectCodeCompletesEnrolment(): void
    {
        $service = $this->service();
        $enrolment = $service->beginEnrolment(self::TYPE, new TestAccount(id: 7));

        $service->confirmEnrolment(self::TYPE, 7, $this->codeFor($enrolment->secret));

        self::assertTrue($service->isEnrolled(self::TYPE, 7));
    }

    public function testConfirmingWithAWrongCodeIsCountedAsAFailedAttempt(): void
    {
        // Otherwise the confirm form is an unmetered oracle against a secret an attacker who
        // reached this page already holds half of.
        $service = $this->service();
        $service->beginEnrolment(self::TYPE, new TestAccount(id: 7));

        try {
            $service->confirmEnrolment(self::TYPE, 7, '000000');
            self::fail('a wrong code should not confirm an enrolment');
        } catch (InvalidCredentials) {
            self::assertSame(1, $this->repository->find(self::TYPE, 7)->failedAttempts);
        }
    }

    public function testConfirmingWithNothingInProgressFails(): void
    {
        $this->expectException(InvalidCredentials::class);

        $this->service()->confirmEnrolment(self::TYPE, 99, '123456');
    }

    public function testRestartingEnrolmentReplacesTheSecretAndClearsConfirmation(): void
    {
        // Re-enrolling means replacing a device. Leaving the old secret confirmed would keep
        // accepting codes from the phone the user is walking away from.
        $service = $this->service();
        $first = $service->beginEnrolment(self::TYPE, new TestAccount(id: 7));
        $service->confirmEnrolment(self::TYPE, 7, $this->codeFor($first->secret));

        $second = $service->beginEnrolment(self::TYPE, new TestAccount(id: 7));

        self::assertNotSame($first->secret, $second->secret);
        self::assertFalse($service->isEnrolled(self::TYPE, 7));
    }

    public function testTheSameEnrolmentCanBeRedisplayedWithoutRotatingTheSecret(): void
    {
        // A refresh of the setup page must show the QR the user is halfway through scanning.
        $service = $this->service();
        $issued = $service->beginEnrolment(self::TYPE, new TestAccount(id: 7));

        $again = $service->describeEnrolment($issued->secret, new TestAccount(id: 7));

        self::assertSame($issued->secret, $again->secret);
        self::assertSame($issued->provisioningUri, $again->provisioningUri);
    }

    // ---- verifying at login ---------------------------------------------------------

    private function enrolled(int $id = 7): string
    {
        $service = $this->service();
        $enrolment = $service->beginEnrolment(self::TYPE, new TestAccount(id: $id));
        $service->confirmEnrolment(self::TYPE, $id, $this->codeFor($enrolment->secret));

        return $enrolment->secret;
    }

    public function testACorrectCodeIsAccepted(): void
    {
        $secret = $this->enrolled();

        $this->service()->verify(self::TYPE, 7, $this->codeFor($secret, 30));

        self::assertSame(0, $this->repository->find(self::TYPE, 7)->failedAttempts);
    }

    public function testACodeFromTheNeighbouringPeriodIsAccepted(): void
    {
        // A phone thirty seconds behind the server still logs in. The code submitted here is
        // one period older than the service's clock — and later than the counter enrolment
        // already spent, so this tests drift rather than replay.
        $secret = $this->enrolled();
        $service = $this->serviceAt(new \DateTime('@' . ($this->now->getTimestamp() + 60)));

        $this->expectNotToPerformAssertions();

        $service->verify(self::TYPE, 7, $this->codeFor($secret, 30));
    }

    public function testAnAccountThatNeverEnrolledCannotVerify(): void
    {
        $this->expectException(InvalidCredentials::class);

        $this->service()->verify(self::TYPE, 7, '123456');
    }

    public function testAnUnconfirmedEnrolmentCannotVerify(): void
    {
        $service = $this->service();
        $enrolment = $service->beginEnrolment(self::TYPE, new TestAccount(id: 7));

        $this->expectException(InvalidCredentials::class);

        $service->verify(self::TYPE, 7, $this->codeFor($enrolment->secret));
    }

    public function testAWrongCodeIsCountedAndEventuallyLocksTheAccount(): void
    {
        // Six digits is one in a million, which only means anything while the number of
        // guesses is bounded. Unbounded, an attacker with the password walks in.
        $this->enrolled();
        $service = $this->service(twoFactor: true, maxAttempts: 3);

        foreach ([1, 2] as $attempt) {
            try {
                $service->verify(self::TYPE, 7, '000000');
                self::fail('a wrong code should not verify');
            } catch (InvalidCredentials $e) {
                self::assertNotInstanceOf(TwoFactorLockedOut::class, $e, "attempt {$attempt} should not lock yet");
            }
        }

        $this->expectException(TwoFactorLockedOut::class);
        $service->verify(self::TYPE, 7, '000000');
    }

    public function testOnceLockedEvenTheCorrectCodeIsRefused(): void
    {
        $secret = $this->enrolled();
        $service = $this->service(twoFactor: true, maxAttempts: 1);

        try {
            $service->verify(self::TYPE, 7, '000000');
        } catch (InvalidCredentials) {
            // expected
        }

        $this->expectException(TwoFactorLockedOut::class);
        $service->verify(self::TYPE, 7, $this->codeFor($secret, 30));
    }

    public function testTheLockExpires(): void
    {
        $secret = $this->enrolled();
        try {
            $this->service(twoFactor: true, maxAttempts: 1)->verify(self::TYPE, 7, '000000');
        } catch (InvalidCredentials) {
            // expected: this is what puts the account into the locked state
        }

        // Move the clock past the lockout and rebuild the service around the later "now".
        $later = new \DateTime('@' . ($this->now->getTimestamp() + 301));
        $service = $this->serviceAt($later, maxAttempts: 1);

        $this->expectNotToPerformAssertions();
        $service->verify(self::TYPE, 7, $this->totp->at($secret, $later->getTimestamp()));
    }

    public function testASuccessfulCodeCannotBeUsedASecondTime(): void
    {
        // A code stays valid for its whole period. Without a spent-counter check, one
        // shoulder-surfed or proxy-logged code works again for up to thirty seconds.
        $secret = $this->enrolled();
        $service = $this->service();
        $code = $this->codeFor($secret, 30);
        $service->verify(self::TYPE, 7, $code);

        $this->expectException(InvalidCredentials::class);
        $service->verify(self::TYPE, 7, $code);
    }

    public function testAReplayedCodeIsRefusedWithoutCountingTowardsTheLockout(): void
    {
        // A double-clicked submit button is the usual cause, and locking someone out for
        // that would be worse than the replay the refusal already prevents.
        $secret = $this->enrolled();
        $service = $this->service();
        $code = $this->codeFor($secret, 30);
        $service->verify(self::TYPE, 7, $code);

        try {
            $service->verify(self::TYPE, 7, $code);
        } catch (InvalidCredentials) {
            // expected
        }

        self::assertSame(0, $this->repository->find(self::TYPE, 7)->failedAttempts);
        self::assertNull($this->repository->find(self::TYPE, 7)->lockedUntil);
    }

    public function testASuccessClearsEarlierFailures(): void
    {
        $secret = $this->enrolled();
        $service = $this->service(twoFactor: true, maxAttempts: 5);

        try {
            $service->verify(self::TYPE, 7, '000000');
        } catch (InvalidCredentials) {
            // expected
        }
        $service->verify(self::TYPE, 7, $this->codeFor($secret, 30));

        self::assertSame(0, $this->repository->find(self::TYPE, 7)->failedAttempts);
    }

    public function testDisablingRemovesTheEnrolmentEntirely(): void
    {
        $this->enrolled();
        $service = $this->service();

        $service->disable(self::TYPE, 7);

        self::assertFalse($service->isEnrolled(self::TYPE, 7));
        self::assertNull($this->repository->find(self::TYPE, 7));
    }
}
