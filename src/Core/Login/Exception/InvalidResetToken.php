<?php

declare(strict_types=1);

namespace Skeletor\Core\Login\Exception;

/**
 * A password-reset link that is missing, malformed, expired or already spent.
 *
 * Typed rather than a bare \Exception because its message is one of the few in this package
 * written for the visitor -- "that link has expired" is what they need to read. Everything the
 * failure table shows verbatim has to be identifiable, or the refactor that routes unknown
 * exceptions to a generic message would swallow it.
 *
 * Deliberately one message for every cause: distinguishing "no such token" from "expired"
 * would let this endpoint be used to probe which addresses have a reset pending.
 */
class InvalidResetToken extends InvalidCredentials
{
}
