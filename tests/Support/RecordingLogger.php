<?php

declare(strict_types=1);

namespace Skeletor\Tests\Support;

use Psr\Log\AbstractLogger;

/**
 * A logger that keeps what it was told.
 *
 * Worth having rather than a NullLogger: Controller::respond() now catches a failed render,
 * logs it, and writes a message into the response body instead of the page. That is the
 * right behaviour in production and a silent pass in a test — a controller test asserting on
 * a redirect would never notice that the page it rendered was actually an error. Asserting
 * the log is empty is how a broken template stays a failing test.
 */
class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string}> */
    public array $records = [];

    public function log($level, $message, array $context = []): void
    {
        $this->records[] = ['level' => (string) $level, 'message' => (string) $message];
    }

    /** @return list<string> */
    public function messages(?string $level = null): array
    {
        return array_values(array_map(
            static fn (array $r): string => $r['message'],
            $level === null ? $this->records : array_filter($this->records, static fn ($r) => $r['level'] === $level)
        ));
    }
}
