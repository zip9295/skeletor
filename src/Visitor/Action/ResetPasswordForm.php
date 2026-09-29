<?php
namespace Skeletor\Visitor\Action;

use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use League\Plates\Engine;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Action\Web\Html;
use Skeletor\Visitor\Service\Login;
use Tamtamchik\SimpleFlash\Flash;

class ResetPasswordForm extends Html
{
    public function __construct(
        Logger $logger, Config $config, Engine $template, private Flash $flash, private Session $session,
        private Login $loginService
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
        $this->setGlobalVariable('pageTitle', $this->template->make('t')->t('Reset password page'));
        $this->setGlobalVariable('cssPath', FRONT_ASSET_URL . '/css/style.css');
//        $this->setGlobalVariable('captchaSiteKey', $this->getConfig()->offsetGet('captcha')->siteKey);

        $error = false;
        try {
            $hash = $request->getAttribute('token');
            $this->loginService->verifyToken($hash);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        return $this->respond('visitor/resetForm', [
            'token' => $hash, 'error' => $error, 'captchaSiteKey' => ''
        ]);
    }
}