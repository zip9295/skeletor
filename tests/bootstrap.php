<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap for the framework's own suite.
 *
 * Run it with `composer install` inside this package and then `vendor/bin/phpunit` from the
 * package root. The apps that embed skeletor do not run these; their suites cover their own
 * code and treat the framework as a dependency.
 *
 * Only the autoloader is needed. Nothing here touches a database: the login tests drive the
 * services through in-memory doubles (tests/Support), because what is worth pinning is the
 * decision each one makes, not Doctrine's ability to store a row.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

// Constants the framework reads through path helpers. Defined defensively so an app-provided
// value would win if this bootstrap ever grew to load one.
defined('APP_PATH') || define('APP_PATH', dirname(__DIR__));
defined('DATA_PATH') || define('DATA_PATH', dirname(__DIR__) . '/data');
// The shipped admin layout interpolates these directly, so a controller test that renders a
// real template needs them defined.
defined('ADMIN_ASSET_URL') || define('ADMIN_ASSET_URL', '/assets/admin');
defined('FRONT_ASSET_URL') || define('FRONT_ASSET_URL', '/assets/front');

// Authenticators stamp the client address on every successful login. In a test run there is
// no request, and an unset REMOTE_ADDR would make that the only reason a test fails.
$_SERVER['REMOTE_ADDR'] ??= '127.0.0.1';
