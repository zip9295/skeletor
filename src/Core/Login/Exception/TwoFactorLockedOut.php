<?php

declare(strict_types=1);

namespace Skeletor\Core\Login\Exception;

/**
 * Too many wrong codes; the authenticator is refusing further attempts for now.
 *
 * Extends InvalidCredentials so any existing catch block keeps working and keeps failing
 * closed — callers that want to say "try again in a minute" rather than "wrong code" can
 * catch this first.
 */
class TwoFactorLockedOut extends InvalidCredentials
{
    public function __construct(public readonly \DateTimeInterface $until, string $message = '')
    {
        parent::__construct($message !== '' ? $message : 'Too many incorrect codes. Please try again later.');
    }
}
