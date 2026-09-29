<?php

namespace Skeletor\Core\Mailer\Service;

use Monolog\LogRecord;

interface MailerInterface
{
    public function getMail();

    public function handle(LogRecord $record): bool;

    public function sendForgotPasswordMail($email, $token, $displayName, $userId);

    /**
     * Send a passwordless login link.
     *
     * Declared here because MagicLinkService calls it on every magic-link request. It was
     * previously implemented on one of the two mailers and on neither interface, so an app
     * wiring the other one got a fatal at the point of sending rather than a type error at
     * the point of wiring.
     *
     * The body comes from the app's "magicLink" email template; the framework ships no email
     * templates of its own.
     */
    public function sendMagicLinkEmail($email, $displayName, $loginUrl);

    public function handleContactForm($name, $message, $subject, $email);
}