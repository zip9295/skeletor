<?php

declare(strict_types=1);

namespace Skeletor\Core\Login\Exception;

/**
 * The submitted address is not an address.
 *
 * Thrown before anything is looked up, so a malformed address costs no query and tells the
 * visitor nothing about which addresses exist.
 */
class InvalidEmailAddress extends \InvalidArgumentException
{
}
