<?php
declare(strict_types=1);

namespace Skeletor\Core\Login\Service;

/**
 * Secure token generation service
 */
class TokenGenerator
{
    /**
     * Generate a cryptographically secure random token
     */
    public function generate(int $length = 64): string
    {
        $bytes = random_bytes($length);
        return bin2hex($bytes);
    }

    /**
     * Generate a URL-safe token
     */
    public function generateUrlSafe(int $length = 64): string
    {
        $bytes = random_bytes($length);
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /**
     * Generate token with hash for verification
     * Returns [token, hash] where token is sent to user, hash is stored in DB
     */
    public function generateWithHash(int $length = 32): array
    {
        $token = $this->generate($length);
        $hash = hash('sha256', $token);

        return [$token, $hash];
    }
}
