<?php
namespace Skeletor\Visitor\Action;

use Skeletor\Core\Config\Config;
use League\Plates\Engine;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Action\Web\Html;
use Tamtamchik\SimpleFlash\Flash;

class RegisterForm extends Html
{
    const LOGIN_FORM_PATH = '/login/loginForm/';

    public function __construct(Logger $logger, Config $config, Engine $template, private Flash $flash)
    {
        parent::__construct($logger, $config, $template);
    }

    public function __invoke(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response
    ) {
        if ($this->flash->some()) {
            $this->setGlobalVariable('messages', $this->flash->display());
        }

        return $this->respond('visitor/registerForm', []);
    }
}