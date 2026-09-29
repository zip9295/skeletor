<?php

declare(strict_types=1);

namespace Skeletor\Core\Security;

use Laminas\Session\ManagerInterface;

/**
 * CSRF token issue and validation.
 *
 * Replaces volnix/csrf, which had seen no release since 2017, had a single unknown maintainer, and
 * generated tokens with sha1(uniqid(sha1($salt), true)) salted from REMOTE_ADDR. uniqid() derives
 * from the system clock, so those tokens were predictable rather than random. It also hand-rolled
 * a constant-time comparison and wrote straight to the $_SESSION superglobal, behind the back of
 * the session manager the rest of the framework uses.
 *
 * This is not "rolling your own crypto": random_bytes() and hash_equals() are the vetted
 * primitives, and every CSRF package worth taking is a thin wrapper over exactly those two calls.
 * The twenty lines around them are what is worth owning.
 *
 * Unlike volnix this is a service rather than a bag of static methods, so the session manager is
 * injected and token storage goes through it. Autowires cleanly: it needs only ManagerInterface,
 * which the container already provides.
 */
class Csrf
{
    /**
     * Byte-identical to the volnix constant. It is the POST field name, so changing it would
     * invalidate every form already rendered in a live session.
     */
    public const TOKEN_NAME = '_csrf_token_645a83a41868941e4692aa31e7235f2';

    /** 32 raw bytes -> 64 hex chars. */
    private const TOKEN_BYTES = 32;

    public function __construct(private readonly ManagerInterface $session) {}

    /**
     * Issue a fresh token, replacing any existing one.
     */
    public function generateToken(string $tokenName = self::TOKEN_NAME): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_BYTES));
        $this->session->getStorage()->offsetSet($tokenName, $token);

        return $token;
    }

    /**
     * The current token, generating one on first use.
     */
    public function getToken(string $tokenName = self::TOKEN_NAME): string
    {
        $storage = $this->session->getStorage();
        $existing = $storage->offsetExists($tokenName) ? $storage->offsetGet($tokenName) : null;

        if (!is_string($existing) || $existing === '') {
            return $this->generateToken($tokenName);
        }

        return $existing;
    }

    public function getTokenName(string $tokenName = self::TOKEN_NAME): string
    {
        return $tokenName;
    }

    /**
     * Validate submitted request data against the stored token.
     *
     * On success the token is rotated so a form cannot be replayed. On failure the stored token is
     * deliberately left alone: regenerating it here would let anyone invalidate a legitimate
     * user's in-flight forms just by posting garbage.
     *
     * @param array<string, mixed> $requestData the whole POST/GET array
     */
    public function validate(array $requestData = [], string $tokenName = self::TOKEN_NAME): bool
    {
        $storage = $this->session->getStorage();
        $expected = $storage->offsetExists($tokenName) ? $storage->offsetGet($tokenName) : null;
        $submitted = $requestData[$tokenName] ?? null;

        if (!is_string($expected) || $expected === '' || !is_string($submitted)) {
            return false;
        }

        if (!hash_equals($expected, $submitted)) {
            return false;
        }

        $this->generateToken($tokenName);

        return true;
    }

    /**
     * Hidden input carrying the token, for direct echo into a form.
     */
    public function getHiddenInputString(string $tokenName = self::TOKEN_NAME): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s"/>',
            htmlspecialchars($tokenName, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->getToken($tokenName), ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * @return array<string, string>
     */
    public function getTokenAsArray(string $tokenName = self::TOKEN_NAME): array
    {
        return [$tokenName => $this->getToken($tokenName)];
    }

    public function getQueryString(string $tokenName = self::TOKEN_NAME): string
    {
        return sprintf('%s=%s', $tokenName, $this->getToken($tokenName));
    }
}
