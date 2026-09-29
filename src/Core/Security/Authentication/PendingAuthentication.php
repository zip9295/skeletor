<?php

declare(strict_types=1);

namespace Skeletor\Core\Security\Authentication;

use Laminas\Session\ManagerInterface;

/**
 * The half-authenticated state between a correct password and a correct second factor.
 *
 * The identity being challenged lives here, in the session, and is never read back from the
 * request. That is the whole point of the class: a two-factor step that takes the account id
 * from a form field is not a second factor, because whoever is posting the form chooses the
 * field. Everything the verify step needs — who, for how long, and whether they asked to be
 * remembered — is written once, at the moment the first factor passed.
 *
 * Deliberately short-lived. A challenge that never completes should expire rather than sit in
 * the session until the cookie does, so an abandoned login on a shared machine is not a
 * standing invitation to anyone who guesses six digits.
 */
class PendingAuthentication
{
    public const SESSION_KEY = 'pendingAuth';

    private const DEFAULT_TTL = 300;

    public function __construct(
        private readonly ManagerInterface $session,
        private readonly int $ttlSeconds = self::DEFAULT_TTL,
    ) {}

    /**
     * Record that the first factor passed, and who for.
     *
     * $redirectPath is captured here rather than recomputed after the challenge because the
     * entity is not loaded again on the verify leg — only its id is trusted, and re-reading
     * a redirect target from the request is how open redirects get in.
     */
    public function begin(
        string $entityType,
        int|string $entityId,
        bool $rememberMe = false,
        ?string $redirectPath = null,
    ): void {
        $this->session->getStorage()->offsetSet(self::SESSION_KEY, [
            'entityType' => $entityType,
            'entityId' => $entityId,
            'rememberMe' => $rememberMe,
            'redirectPath' => $redirectPath,
            'enrolmentSecret' => null,
            'expiresAt' => time() + $this->ttlSeconds,
        ]);
    }

    public function isPending(): bool
    {
        return $this->read() !== null;
    }

    public function getEntityType(): ?string
    {
        return $this->read()['entityType'] ?? null;
    }

    public function getEntityId(): int|string|null
    {
        return $this->read()['entityId'] ?? null;
    }

    public function shouldRememberMe(): bool
    {
        return (bool) ($this->read()['rememberMe'] ?? false);
    }

    public function getRedirectPath(): ?string
    {
        return $this->read()['redirectPath'] ?? null;
    }

    /**
     * Hold the secret issued by an in-progress enrolment, so re-rendering the setup page
     * shows the same QR code instead of silently rotating it under the user.
     *
     * It is the same secret already stored (encrypted) against the account; keeping the
     * plaintext in the session for the length of the challenge is what avoids decrypting it
     * again on every render, and it dies with the pending state.
     */
    public function rememberEnrolmentSecret(string $secret): void
    {
        $state = $this->read();
        if ($state === null) {
            return;
        }

        $state['enrolmentSecret'] = $secret;
        $this->session->getStorage()->offsetSet(self::SESSION_KEY, $state);
    }

    public function getEnrolmentSecret(): ?string
    {
        return $this->read()['enrolmentSecret'] ?? null;
    }

    public function clear(): void
    {
        $this->session->getStorage()->offsetUnset(self::SESSION_KEY);
    }

    /**
     * The stored state, or null when there is none or it has expired.
     *
     * Expiry is checked on read rather than swept on a timer, and an expired state is
     * cleared as it is found — so a stale challenge cannot be completed even if the session
     * itself is still alive.
     *
     * @return array<string, mixed>|null
     */
    private function read(): ?array
    {
        $storage = $this->session->getStorage();
        $state = $storage->offsetExists(self::SESSION_KEY) ? $storage->offsetGet(self::SESSION_KEY) : null;

        if (!is_array($state) || !isset($state['entityType'], $state['entityId'], $state['expiresAt'])) {
            return null;
        }

        if ($state['expiresAt'] < time()) {
            $this->clear();

            return null;
        }

        return $state;
    }
}
