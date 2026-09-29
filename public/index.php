<?php

use Psr\Log\LoggerInterface;
use Skeletor\Core\App\WebSkeletor;
use Skeletor\Core\Error\BootstrapErrorHandler;

error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../config/constants.php");
include(APP_PATH . "/vendor/autoload.php");

// Must come before anything that can fail. Covers the window between here and the point where
// bootstrap builds the logger and Monolog's ErrorHandler takes over; without it, a fatal in that
// window is a blank 500 with nothing written to the log.
BootstrapErrorHandler::register();

$path = getenv('APPLICATION');
$env = getenv('APPLICATION_ENV');
if($env !== 'production') {
//    \Tracy\Debugger::enable(\Tracy\Debugger::Development, DATA_PATH . '/logs');
//    $panel = new \A3S\Tracy\Service\DoctrinePanel();
//    \Tracy\Debugger::getBar()->addPanel($panel);
//    \Tracy\Debugger::log('Tracy initialized');
}
if($path === 'frontend') {
    ini_set('display_errors', 0);
}

try {
    /* @var \DI\Container $container */
//    $container = require sprintf('%s/config/%s/bootstrap.php', APP_PATH, $path);
    $container = require sprintf('%s/config/bootstrap.php', APP_PATH);
    $app = new WebSkeletor($container, $container->get(LoggerInterface::class));
} catch (\Throwable $e) {
    if (isset($app) && $app) {
        $app->handleErrors($e);
        exit();
    }
    // The app never came up, so there is no container and no logger to report through.
    BootstrapErrorHandler::handleException($e);
    exit();
}
$app->respond();
