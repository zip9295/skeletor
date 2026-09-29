<?php

declare(strict_types=1);

namespace Skeletor\Tests\Support;

use Monolog\LogRecord;
use Skeletor\Core\Mailer\Service\MailerInterface;

/**
 * A mailer that keeps the letters instead of sending them.
 *
 * Implements the interface rather than stubbing it, so a method added to MailerInterface
 * without an implementation shows up here as a fatal at test time — which is exactly how
 * sendMagicLinkEmail() managed to be called by the framework while existing on only one of
 * the two shipped mailers.
 */
class RecordingMailer implements MailerInterface
{
    /** @var list<array{email: string, displayName: string, loginUrl: string}> */
    public array $magicLinks = [];

    /** @var list<array{email: string, token: string, displayName: string, userId: mixed}> */
    public array $forgotPasswords = [];

    public function getMail()
    {
        return null;
    }

    public function handle(LogRecord $record): bool
    {
        return true;
    }

    public function sendForgotPasswordMail($email, $token, $displayName, $userId)
    {
        $this->forgotPasswords[] = compact('email', 'token', 'displayName', 'userId');
    }

    public function sendMagicLinkEmail($email, $displayName, $loginUrl)
    {
        $this->magicLinks[] = compact('email', 'displayName', 'loginUrl');
    }

    public function handleContactForm($name, $message, $subject, $email)
    {
    }

    public function lastMagicLinkUrl(): ?string
    {
        return $this->magicLinks ? $this->magicLinks[array_key_last($this->magicLinks)]['loginUrl'] : null;
    }
}
