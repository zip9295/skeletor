<?php
namespace Skeletor\Core\Mailer\Service;

use Skeletor\Core\Config\Config;
use League\Plates\Engine;
use Monolog\LogRecord;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer extends \Monolog\Handler\AbstractHandler implements MailerInterface
{
    public function __construct(
        public readonly PHPMailer $mail, public readonly Config $config, private ?Engine $template = null
    ) { }

    public function getMail()
    {
        return $this->mail;
    }

    /**
     * @param LogRecord $record
     * @return bool
     */
    public function handle(LogRecord $record): bool
    {
        return $this->handleApplicationError($record);
    }

    public function sendForgotPasswordMail($email, $token, $displayName, $userId)
    {
        $token = sprintf('$%s$%s', $userId, $token);
        if (getenv('APPLICATION') === 'backend') {
            $resetUrl = sprintf('%s/login/resetPasswordForm/%s/', $this->config->offsetGet('adminUrl'), $token);
        } else {
            $resetUrl = sprintf('%s/login/resetPasswordForm/%s/', $this->config->offsetGet('baseUrl'), $token);
        }
        $body = $this->render('forgotPassword', [
            'displayName' => $displayName,
            'resetUrl' => $resetUrl,
            'baseUrl' => $this->config->offsetGet('baseUrl')
        ]);

        $this->mail->setFrom($this->config->mailer->from, $this->config->appName);
        $this->mail->addAddress($email);
        $this->mail->Subject = sprintf('Password recovery request from %s', $this->config->appName);
        $this->mail->CharSet = PHPMailer::CHARSET_UTF8;
        $this->mail->msgHTML($body);
        $this->mail->AltBody = strip_tags($body);
        $this->mail->send();
    }


    /**
     * Send a passwordless login link.
     *
     * The PHPMailer counterpart of MailerSendMailer::sendMagicLinkEmail(); both are required
     * by MailerInterface, and this one was missing, so any app wiring the PHPMailer transport
     * fataled the first time a magic link was requested.
     *
     * Renders the app's "magicLink" email template with displayName, loginUrl and baseUrl.
     */
    public function sendMagicLinkEmail($email, $displayName, $loginUrl)
    {
        $body = $this->render('magicLink', [
            'displayName' => $displayName,
            'loginUrl' => $loginUrl,
            'baseUrl' => $this->config->offsetGet('baseUrl'),
        ]);

        $this->mail->setFrom($this->config->mailer->from, $this->config->appName);
        $this->mail->addAddress($email);
        $this->mail->Subject = $this->config->magicLink?->subject
            ?? sprintf('Login link for %s', $this->config->offsetGet('appName'));
        $this->mail->CharSet = PHPMailer::CHARSET_UTF8;
        $this->mail->msgHTML($body);
        $this->mail->AltBody = strip_tags($body);
        $this->mail->send();
    }

    public function render($template, $data = [])
    {
//        $template = sprintf('%s/%s', 'email' ,$template);
        try {
            if ($this->template->exists($template)) {
                $html = $this->template->render($template, ['data' => $data]);
            } else {
                $tpl = 'emailTheme::' . $template;
                $html = $this->template->render($tpl, ['data' => $data]);
            }
            return $html;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }

    /**
     * @param $name
     * @param $message
     * @param $subject
     * @return void
     */
    public function handleContactForm($name, $message, $subject, $email)
    {
        $body = sprintf('<p>Message subject: %s</p>', $subject)
            . sprintf('<p>Message content: %s</p>', $message)
            . sprintf('<p>Email address: %s</p>', $email);

        // From stays the configured sender so SPF/DKIM still line up; the visitor goes in
        // Reply-To instead, which is where a reply should land anyway.
        $this->mail->setFrom($this->config->mailer->from, $this->config->appName);
        $this->mail->addReplyTo($email, $name);
        foreach ($this->config->mailer->recipients->contactForm as $targetMail) {
            $this->mail->addAddress($targetMail);
        }
        $this->mail->Subject = sprintf('A new message has been sent from %s by %s',
            $this->config->offsetGet('appName'), $name
        );
        $this->mail->CharSet = PHPMailer::CHARSET_UTF8;
        $this->mail->msgHTML($body);
        $this->mail->AltBody = strip_tags($body);
        $this->send();
    }

    /**
     * @param LogRecord $record
     * @return true
     */
    public function handleApplicationError(LogRecord $record)
    {
        $skipPatterns = [
            '/^\/\/.*\.php7$/', // matches: //*.php7
//            '/^.*\.php$/', // matches: *.php
        ];
        $skipUris = [
            'cdn.js',
        ];
        if (str_contains($record->message, 'Invalid uri string received')) {
            $found = array_map(function ($uri) use ($record, $skipPatterns) {
                foreach ($skipPatterns as $pattern) {
                    preg_match($pattern, $_SERVER['REQUEST_URI'], $matches);
                    if (count($matches)) {
                        return true;
                    }
                }
                if (str_contains($_SERVER['REQUEST_URI'], $uri)) {
                    return true;
                }
            }, $skipUris);
        }
        // @TODO add some ban mechanism
        if (count($found) && $found[0] === true) {
            // skip sending mail
            header('Location: ' . $this->config->baseUrl);
            exit();
        }

        $body = $record->message . PHP_EOL .
            $record->channel . PHP_EOL .
            $record->datetime->format('y/m/d H:i:s') . PHP_EOL .
            $record->level->getName() . PHP_EOL;

        $this->mail->setFrom($this->config->mailer->from, "ITS Mailer");
        foreach ($this->config->mailer->recipients->errorNotice as $targetMail) {
            $this->mail->addAddress($targetMail);
        }
        $this->mail->Subject = sprintf('%s application error !', $this->config->offsetGet('appName'));
        $this->mail->CharSet = PHPMailer::CHARSET_UTF8;
        $this->mail->msgHTML($body);
        $this->mail->AltBody = strip_tags($body);
        $this->mail->send();

        return true;
    }

    /**
     * @return void
     */
    protected function send()
    {
        try {
            $this->mail->send();
        } catch (\Exception $e) {
//            $this->logger->log(\Monolog\Level::Error,
//                sprintf('Could not send mail %s: %s', $this->mail->Subject, $e->getMessage()));
        }
    }
}
