<?php

namespace Skeletor\Core\Action\Web;

use GuzzleHttp\Psr7\ServerRequest as Request;
use Skeletor\Core\Config\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class Git.
 *
 * @package Wellsite\Controller
 */
class Git
{

    /**
     * @var Config
     */
    public $config;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * Controller constructor.
     *
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        Logger $logger
    ) {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Executes git pull command from cli, and logs if there was a success.
     *
     * @param Request  $request
     * @param ResponseInterface $response
     * @param callable $next
     *
     * @return ResponseInterface
     *@throws \Exception if command fails.
     *
     */
    public function __invoke(Request $request, ResponseInterface $response, callable $next)
    {
        $msg = 'Git pull succeeded at :' . date('Y/m/d H:i:s');
        if (!$this->execute()) {
            $msg = 'Git pull failed at :' . date('Y/m/d H:i:s');
            throw new \Exception($msg);
        }
        $this->logger->notice($msg);
        $response->getBody()->write($msg);

        return $response;
    }

    /**
     * Executes gut pull command.
     *
     * @TODO Implement Symphony Process component.
     * @TODO Implement params from config.
     *
     * @return bool
     */
    public function execute()
    {
        echo shell_exec("cd /srv/www/path && /usr/bin/git pull https://user:pass@bitbucket.org/owner/repo.git develop 2>&1");

        return true;
    }
}