<?php
namespace Skeletor\Core\Action\Web;

use GuzzleHttp\Psr7\Response;
use Skeletor\Core\Config\Config;
use League\Plates\Engine;
use Psr\Log\LoggerInterface as Logger;

abstract class Json
{
    /**
     * @var Logger
     */

    public $response;

    /**
     * ParseReportAction constructor.
     * @param Logger $logger
     * @param Config $config
     */
    public function __construct(
        private Logger $logger, private Config $config
    ) {
        $this->response = new Response();
    }

    public function respond($data) : Response
    {
        $this->response->getBody()->write(json_encode($data));
        $this->response->getBody()->rewind();

        return $this->response;
    }

    public function setGlobalVariable($name, $value)
    {
        $this->template->addData([$name => $value]);
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getLogger()
    {
        return $this->logger;
    }
}