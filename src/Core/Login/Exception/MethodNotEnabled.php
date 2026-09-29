<?php

declare(strict_types=1);

namespace Skeletor\Core\Login\Exception;

use Skeletor\Core\Mapper\NotFoundException;

/**
 * This endpoint does not exist in this application.
 *
 * A NotFoundException subclass, so WebSkeletor still renders the app's 404 -- but a distinct
 * type, because plain NotFoundException already means "no account with that address" inside
 * the login flows. While the two shared a class, the only thing keeping them apart was that
 * the policy check happened to run outside the try block. LoginController::fail() now rethrows
 * this on sight, so the separation is stated rather than positional.
 */
class MethodNotEnabled extends NotFoundException
{
}
