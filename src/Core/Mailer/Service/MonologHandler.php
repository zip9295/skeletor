<?php

namespace Skeletor\Core\Mailer\Service;

use Monolog\Handler\HandlerInterface;
use Monolog\LogRecord;

class MonologHandler implements HandlerInterface
{
    private $mail;

    private $isHandling = false;

    public function isHandling(LogRecord $record): bool
    {
        return false;
    }

    public function handleBatch(array $records): void
    {
//        var_dump($records);
    }

    public function close(): void
    {

    }


    public function setMail(PhpMailer $mail)
    {
        $this->mail = $mail;
    }

    public function handle(\Monolog\LogRecord $record): bool
    {
        if ($record->level >= \Monolog\Level::Error) {
            $this->mail->handleApplicationError($record);
        }

        return false;
    }
}