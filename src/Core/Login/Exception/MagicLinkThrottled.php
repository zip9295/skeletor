<?php

declare(strict_types=1);

namespace Skeletor\Core\Login\Exception;

/**
 * A magic link was asked for again before the cooldown expired.
 *
 * Separate from InvalidCredentials because it is not a credential problem: the account is
 * fine, the request is simply too soon. Carries the moment another request will be accepted
 * so the caller can say something useful instead of "try later".
 */
class MagicLinkThrottled extends \RuntimeException
{
    public function __construct(public readonly \DateTimeInterface $retryAfter, string $message = '')
    {
        parent::__construct($message !== '' ? $message : 'A login link was just sent. Please check your inbox.');
    }
}
