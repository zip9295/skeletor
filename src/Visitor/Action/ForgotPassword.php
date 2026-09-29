<?php
namespace Skeletor\Visitor\Action;

use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use League\Plates\Engine;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Action\Web\Html;
use Skeletor\Core\Mailer\Service\PhpMailer;
use Skeletor\Visitor\Filter\ForgotPassword as ForgotPasswordFilter;
use Skeletor\Validator\ValidatorException;
use Skeletor\Visitor\Service\Login as LoginService;
use Tamtamchik\SimpleFlash\Flash;
use Skeletor\Visitor\Mapper\ForgotPassword as ForgotPasswordMapper;

class ForgotPassword extends Html
{
    const FORGOT_PASSWORD_FORM_PATH = '/login/forgotPasswordForm';

    public function __construct(
        Logger                       $logger, Config $config, Engine $template, private Flash $flash, private Session $session,
        private LoginService         $loginService, private PhpMailer $mailer, private ForgotPasswordMapper $forgotPassword,
        private ForgotPasswordFilter $forgotPasswordFilter
    ) {
        parent::__construct($logger, $config, $template);
    }

    public function __invoke(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response
    ) {
        if ($this->session->getStorage()->offsetGet('loggedIn')) {
            return $this->redirect($this->session->getStorage()->offsetGet('redirectPath'));
        }
        try {
            $data = $this->forgotPasswordFilter->filter($request->getParsedBody());
        } catch (\Skeletor\Core\Validator\ValidatorException $e) {
            foreach ($this->forgotPasswordFilter->getErrors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->flash->error($this->template->make('t')->t($message));
                }
            }
            return $this->redirect(static::FORGOT_PASSWORD_FORM_PATH);
        } catch (\Exception $e) {
            $this->flash->error($this->template->make('t')->t($e->getMessage()));
            return $this->redirect(static::FORGOT_PASSWORD_FORM_PATH);
        }
        try {
            $visitor = $this->loginService->getByEmail($data['email']);
        } catch(\Exception $e) {
            $this->flash->error($this->template->make('t')->t('Email not found in the system.'));
            return $this->redirect(static::FORGOT_PASSWORD_FORM_PATH);
        }
        try {
            $pwd = $this->loginService->generateForgotPasswordHash();
            $this->mailer->sendForgotPasswordMail($visitor->getEmail(), $pwd, $visitor->getFirstName(), $visitor->getId());
            $verificationHash = password_hash($pwd, PASSWORD_BCRYPT);
            $this->forgotPassword->insert([
                'visitorId' => $visitor->getId(),
                'token' => $verificationHash,
            ]);
        } catch(\Exception $e) {
            $this->flash->error($this->template->make('t')->t('An unexpected error ocurred, please try again.'));
            return $this->redirect(static::FORGOT_PASSWORD_FORM_PATH);
        }


        return $this->redirect('/login/forgotPasswordForm?sent');
    }
}