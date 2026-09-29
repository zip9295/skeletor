<?php

namespace Skeletor\Core\Action\Web;

use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Config\Config;
use \League\Plates\Engine;
use Laminas\Session\ManagerInterface as Session;
use Tamtamchik\SimpleFlash\Flash;

class Index extends Html
{
    /**
     * @var Session
     */
    private $session;
    /**
     * @var Flash
     */
    private $flash;

    /**
     * HomeAction constructor.
     * @param Logger $logger
     * @param Config $config
     * @param Engine $template
     */
    public function __construct(
        Logger $logger, Config $config, Engine $template, Session $session, Flash $flash
    ) {
        parent::__construct($logger, $config, $template);
        $this->flash = $flash;
        $this->session = $session;
        $this->setGlobalVariable('loggedIn', $this->session->getStorage()->offsetGet('loggedIn'));
    }

    /**
     * Parses data for provided merchantId
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request request
     * @param \Psr\Http\Message\ResponseInterface $response response
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception
     */
    public function __invoke(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response
    ) {
        if ($this->session->getStorage()->offsetGet('loggedIn')) {
            if ($this->session->getStorage()->offsetGet('loggedInEntityType') === 'user') {
                $url = $this->getConfig()->offsetGet('adminUrl') . '/user/view/';
            }
        }

        return $response->withStatus(302)->withHeader('Location', $url);
    }

}