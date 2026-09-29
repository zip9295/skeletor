<?php
namespace Skeletor\Core\Action\Web;

use Skeletor\Core\Config\Config;
use League\Plates\Engine;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class Git.
 *
 * @package Wellsite\Controller
 */
class NotFound implements NotFoundInterface
{

    /**
     * @var Config
     */
    public $config;

    /**
     * @var Logger
     */
    public $logger;

    public $template;

    /**
     * Controller constructor.
     *
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        Logger $logger,
        Engine $template
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->template = $template;
    }

    public function __invoke(Request $request, Response $response, $requestedRoute)
    {
        $this->template->addData(['pageTitle' => '404', 'loggedIn' => false]);
        $response->getBody()->write($this->template->render('index/404', ['route' => $requestedRoute]));
        $response->getBody()->rewind();
        $response = $response->withStatus(404);

        return $response;
    }

}