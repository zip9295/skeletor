<?php
namespace Skeletor\Visitor\Action;

use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use League\Plates\Engine;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Action\Web\Html;
use Skeletor\Visitor\Mapper\ForgotPassword;
use Skeletor\Visitor\Service\Login;
use Tamtamchik\SimpleFlash\Flash;
use Skeletor\Visitor\Filter\ResetPassword as ResetPasswordFilter;

class ResetPassword extends Html
{
    const LOGIN_FORM_PATH = '/login/loginForm';

    public function __construct(
        Logger        $logger, Config $config, Engine $template, private Flash $flash, private Session $session,
        private Login $loginService, private ForgotPassword $forgotPassword, private ResetPasswordFilter $resetPasswordFilter
    ) {
        parent::__construct($logger, $config, $template);
    }

    public function __invoke(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response
    ) {
        if ($this->flash->some()) {
            $this->setGlobalVariable('messages', $this->flash->display());
        }

        if ($this->session->getStorage()->offsetGet('loggedIn')) {
            return $this->redirect($this->session->getStorage()->offsetGet('redirectPath'));
        }
        if ($this->session->getStorage()->offsetGet('loggedIn')) {
            return $this->redirect($this->session->getStorage()->offsetGet('redirectPath'));
        }
        try {
            $hash = $request->getAttribute('token');
            $tokenData = $this->loginService->verifyToken($hash);
            $userId = $tokenData['visitorId'];
            $data = $this->resetPasswordFilter->filter($request->getParsedBody());
            $this->loginService->resetPassword($userId, $data['password']);
        } catch (\Skeletor\Core\Validator\ValidatorException $e) {
            foreach ($this->resetPasswordFilter->getErrors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->flash->error($this->template->make('t')->t($message));
                }
            }

            return $this->redirect(sprintf('/login/resetPasswordForm/%s/', $hash));
        } catch (\Exception $e) {
            $this->flash->error($this->template->make('t')->t($e->getMessage()));

            return $this->redirect(sprintf('/login/resetPasswordForm/%s/', $hash));
        }
        $this->forgotPassword->updateField('token', $hash.'-used', $tokenData['tokenId']);
        $this->flash->success($this->template->make('t')->t('You have successfully reset your password. You can now sign in.'));

        return $this->redirect(static::LOGIN_FORM_PATH);
    }
}