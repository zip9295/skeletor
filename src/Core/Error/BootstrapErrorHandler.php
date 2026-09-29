<?php

namespace Skeletor\Core\Error;

/**
 * Last-resort error logging for the boot phase.
 *
 * Monolog's ErrorHandler is only registered once the container has built the LoggerInterface
 * service. Anything that dies before that — a missing class after a forgotten
 * `composer dump-autoload`, a bad config value, an incompatible method signature — produces a
 * blank 500 that is never written anywhere. This handler covers exactly that window: register it
 * immediately after the autoloader, and call release() once Monolog has taken over.
 *
 * It deliberately depends on nothing but the SPL: no container, no Monolog, no config. The whole
 * point is to still work when the things it would otherwise depend on are the things that broke.
 *
 * Note that compile-time fatals (E_COMPILE_ERROR, E_PARSE) are NOT Throwable and can never be
 * caught by a try/catch in the front controller. register_shutdown_function() + error_get_last()
 * is the only mechanism that sees them, which is why this class exists rather than just widening
 * the front controller's catch to \Throwable.
 */
final class BootstrapErrorHandler
{
    /** Error levels that end the request. Everything else is left to the normal handler. */
    private const FATAL_LEVELS = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;

    private static bool $registered = false;

    private static bool $active = false;

    private static ?string $logFile = null;

    /**
     * @param string|null $logFile Defaults to the same daily file Monolog writes to,
     *                             data/logs/<Y-m>/<host>-backend-<d>.log
     */
    public static function register(?string $logFile = null): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;
        self::$active = true;
        self::$logFile = $logFile ?? self::defaultLogFile();

        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Hand over to Monolog. Call this right after ErrorHandler::register($logger) in bootstrap,
     * otherwise both handlers log the same fatal and every entry appears twice.
     */
    public static function release(): void
    {
        self::$active = false;
    }

    public static function isActive(): bool
    {
        return self::$active;
    }

    /**
     * Always logs, even after release(): this is also the front controller's explicit fallback for
     * a boot failure it caught itself, and swallowing that would recreate the blank 500. There is
     * no double-log risk, because Monolog's ErrorHandler replaces our set_exception_handler().
     */
    public static function handleException(\Throwable $e): void
    {
        self::write(sprintf(
            'Uncaught %s: %s in %s:%d',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ), $e->getTraceAsString());
        self::respond($e->getMessage());
    }

    public static function handleShutdown(): void
    {
        if (!self::$active) {
            return;
        }
        $error = error_get_last();
        if ($error === null || ($error['type'] & self::FATAL_LEVELS) === 0) {
            return;
        }
        self::write(sprintf(
            '%s: %s in %s:%d',
            self::levelName($error['type']),
            $error['message'],
            $error['file'],
            $error['line']
        ));
        self::respond($error['message']);
    }

    /**
     * Append one line to the log. Never throws: if this fails there is nowhere left to complain to,
     * so fall back to the SAPI error log and give up.
     */
    private static function write(string $message, ?string $trace = null): void
    {
        $line = sprintf(
            '[%s] bootstrap.CRITICAL: %s %s' . PHP_EOL,
            date('Y-m-d\TH:i:sP'),
            $message,
            $trace !== null ? json_encode(['trace' => $trace], JSON_UNESCAPED_SLASHES) : '[]'
        );

        try {
            $file = self::$logFile;
            if ($file !== null) {
                $dir = dirname($file);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0775, true);
                }
                if (is_dir($dir) && @file_put_contents($file, $line, FILE_APPEND | LOCK_EX) !== false) {
                    return;
                }
            }
        } catch (\Throwable $ignored) {
            // fall through to error_log below
        }

        error_log(rtrim($line));
    }

    /**
     * Make the failure visible to whoever triggered it, without leaking internals in production.
     */
    private static function respond(string $message): void
    {
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, 'Fatal error during bootstrap: ' . $message . PHP_EOL);
            return;
        }

        if (!headers_sent()) {
            http_response_code(500);
        }

        if (strtolower((string) getenv('APPLICATION_ENV')) === 'production') {
            echo 'An error has occurred.';
            return;
        }

        echo 'Fatal error during bootstrap: ' . $message;
    }

    private static function defaultLogFile(): ?string
    {
        if (!defined('DATA_PATH')) {
            return null;
        }

        return sprintf(
            '%s/logs/%s/%s-backend-%s.log',
            DATA_PATH,
            date('Y-m'),
            gethostname(),
            date('d')
        );
    }

    private static function levelName(int $type): string
    {
        return match ($type) {
            E_ERROR => 'E_ERROR',
            E_PARSE => 'E_PARSE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_USER_ERROR => 'E_USER_ERROR',
            default => 'E_UNKNOWN(' . $type . ')',
        };
    }
}
