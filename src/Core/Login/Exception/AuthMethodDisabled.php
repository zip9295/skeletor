<?php

declare(strict_types=1);

namespace Skeletor\Core\Login\Exception;

/**
 * Credentials were presented for a login method this application does not have switched on,
 * or that this particular account cannot use.
 *
 * Distinct from InvalidCredentials because it is not a judgement about the credentials at
 * all — they were never examined. It is the backstop behind the controller's 404: if a route,
 * an ACL entry or a hand-built request gets past that, this is what stops a disabled method
 * from producing a session anyway.
 */
class AuthMethodDisabled extends \RuntimeException
{
}
