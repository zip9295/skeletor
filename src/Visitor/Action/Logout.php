<?php
namespace Skeletor\Visitor\Action;

use Skeletor\Core\Config\Config;
use League\Plates\Engine;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Action\Web\Html;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Visitor\Filter\Login as Filter;
use Skeletor\Visitor\Service\Login as LoginService;
use Tamtamchik\SimpleFlash\Flash;
use Laminas\Session\SessionManager;

class Logout extends Html
{
    const LOGGED_OUT = 'You have successfully signed out.';

    public function __construct(
        private SessionManager $session, private Flash $flash, Logger $logger, Config $config, Engine $template
    ) {
        parent::__construct($logger, $config, $template);
    }

    public function __invoke(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response
    ) {
        $this->session->getStorage()->offsetUnset('loggedIn');
        $this->session->getStorage()->offsetUnset('loggedInRole');
        $this->session->getStorage()->offsetUnset('redirectPath');
        $this->session->getStorage()->offsetUnset('loggedInEmail');
        $this->session->forgetMe();
        $this->flash->success(self::LOGGED_OUT);

        return $this->redirect('/');
    }
}