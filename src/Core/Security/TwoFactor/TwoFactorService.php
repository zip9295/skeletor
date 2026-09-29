<?php

declare(strict_types=1);

namespace Skeletor\Core\Security\TwoFactor;

use Skeletor\Core\Security\Authentication\AuthenticatableInterface;
use Skeletor\Core\Security\Authentication\TwoFactorAwareInterface;
use Skeletor\Core\Security\AuthPolicy;
use Skeletor\Core\Login\Exception\InvalidCredentials;
use Skeletor\Core\Login\Exception\TwoFactorLockedOut;
use Skeletor\Core\Login\Repository\TwoFactorSecretRepository;

/**
 * Two-factor policy: who needs it, enrolling them, and judging a submitted code.
 *
 * Works for any authenticatable — secrets are keyed by (entityType, entityId), so nothing
 * here knows about users specifically.
 *
 * The switch is whole-application (auth.twoFactor), because "some accounts here need a
 * second factor and some do not" is a decision about accounts, not about configuration. An
 * entity may still opt itself out by implementing TwoFactorAwareInterface -- narrowing
 * only, in the same direction as AuthPolicy::availableTo(): config cannot be widened from
 * an entity class.
 *
 * Off by default, which is what lets an app ship this code without using it.
 */
class TwoFactorService
{
    /**
     * @param int $window periods of clock drift accepted either side of now
     * @param int $maxAttempts wrong codes before the account is locked
     * @param int $lockSeconds how long a lockout lasts
     */
    public function __construct(
        private TwoFactorSecretRepository $repository,
        private TotpGenerator $totp,
        private SecretCipher $cipher,
        private \DateTime $dt,
        private AuthPolicy $policy,
        private string $issuer,
        private QrCodeRendererInterface $qrRenderer = new NullQrCodeRenderer(),
        private int $window = 1,
        private int $maxAttempts = 5,
        private int $lockSeconds = 300,
    ) {}

    /**
     * Must this account pass a second factor?
     *
     * The application decides first. Only when it has two-factor switched on does the account
     * get a say, and then only to opt out -- a donor on a public site should not be dragged
     * through an authenticator app because the staff dashboard requires one.
     */
    public function isRequiredFor(AuthenticatableInterface $entity): bool
    {
        if (!$this->policy->twoFactorRequired()) {
            return false;
        }

        if ($entity instanceof TwoFactorAwareInterface) {
            return $entity->requiresTwoFactor();
        }

        return true;
    }

    /**
     * Has this account finished enrolling? An unconfirmed row does not count — see
     * TwoFactorSecret for why the two states are separate.
     */
    public function isEnrolled(string $entityType, int|string $entityId): bool
    {
        return $this->repository->find($entityType, (int) $entityId)?->isConfirmed() === true;
    }

    /**
     * Start enrolment: mint a secret, store it encrypted and unconfirmed, and hand back
     * what the setup page has to display.
     *
     * Calling this again before confirming issues a new secret and discards the old one.
     * That is deliberate — a half-finished enrolment on a device the user no longer has
     * must not stay usable — but it does mean the setup page must not re-issue on every
     * render. LoginController keeps the issued secret in the pending-login state so a
     * refresh or a mistyped code shows the same QR the user is halfway through scanning.
     */
    public function beginEnrolment(string $entityType, AuthenticatableInterface $entity): TwoFactorEnrolment
    {
        $secret = $this->totp->generateSecret();
        $this->repository->startEnrolment($entityType, (int) $entity->getId(), $this->cipher->encrypt($secret));

        return $this->enrolmentFor($secret, $entity);
    }

    /**
     * Present an already-issued secret again without touching storage.
     */
    public function describeEnrolment(string $secret, AuthenticatableInterface $entity): TwoFactorEnrolment
    {
        return $this->enrolmentFor($secret, $entity);
    }

    /**
     * Finish enrolment by proving the authenticator app works.
     *
     * @throws InvalidCredentials when there is nothing to confirm or the code is wrong
     */
    public function confirmEnrolment(string $entityType, int|string $entityId, string $code): void
    {
        $record = $this->repository->find($entityType, (int) $entityId);
        if (!$record) {
            throw new InvalidCredentials('There is no two-factor setup in progress for this account.');
        }

        $counter = $this->totp->matchCounter(
            $this->cipher->decrypt($record->secret),
            $code,
            $this->now(),
            $this->window
        );

        if ($counter === null) {
            // Failures during enrolment count too: without that, the confirm form is an
            // unmetered oracle against a secret an attacker may already hold half of.
            $this->repository->recordFailure($record, $this->maxAttempts, $this->lockSeconds);

            throw new InvalidCredentials('That code is not correct. Check the time on your device and try again.');
        }

        $this->repository->confirm($record, $counter);
    }

    /**
     * Judge a code at login.
     *
     * @throws TwoFactorLockedOut after too many wrong codes
     * @throws InvalidCredentials for a wrong, reused, or not-yet-enrolled code
     */
    public function verify(string $entityType, int|string $entityId, string $code): void
    {
        $record = $this->repository->find($entityType, (int) $entityId);
        if (!$record || !$record->isConfirmed()) {
            throw new InvalidCredentials('Two-factor authentication is not set up for this account.');
        }

        if ($record->isLocked($this->dt)) {
            throw new TwoFactorLockedOut($record->lockedUntil);
        }

        $counter = $this->totp->matchCounter(
            $this->cipher->decrypt($record->secret),
            $code,
            $this->now(),
            $this->window
        );

        if ($counter === null) {
            $this->repository->recordFailure($record, $this->maxAttempts, $this->lockSeconds);

            if ($record->isLocked($this->dt)) {
                throw new TwoFactorLockedOut($record->lockedUntil);
            }

            throw new InvalidCredentials('Invalid or expired code.');
        }

        if ($record->hasSpent($counter)) {
            // Deliberately not counted as a failure: a double-submitted form is the common
            // cause, and locking someone out for clicking twice would be worse than the
            // replay this refuses. The refusal itself is what closes the window.
            throw new InvalidCredentials('That code has already been used. Wait for the next one.');
        }

        $this->repository->recordSuccess($record, $counter);
    }

    /**
     * Remove two-factor from an account entirely. An administrative action — nothing in the
     * login flow calls it, because a login flow that can turn off the second factor is not
     * a second factor.
     */
    public function disable(string $entityType, int|string $entityId): void
    {
        $this->repository->delete($entityType, (int) $entityId);
    }

    private function enrolmentFor(string $secret, AuthenticatableInterface $entity): TwoFactorEnrolment
    {
        $uri = $this->totp->getProvisioningUri($secret, $entity->getEmail(), $this->issuer);

        return new TwoFactorEnrolment($secret, $uri, $this->qrRenderer->render($uri));
    }

    private function now(): int
    {
        return $this->dt->getTimestamp();
    }
}
