<?php
namespace Skeletor\Core\App;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest as Request;
use Skeletor\Core\Config\Config;
use Psr\Log\LoggerInterface as Logger;

class CliSkeletor
{
    /**
     * @var \DI\Container
     */
    private $dic;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Logger
     */
    private $logger;

    private $timer;

    private $map;

    /**
     * WebSkeletor constructor.
     *
     * @param \DI\Container $dic
     */
    public function __construct(\DI\Container $dic, Logger $logger)
    {
        $this->dic = $dic;
        $this->logger = $logger;
        $this->map = $dic->get(Config::class)->offsetGet('cliMap')->toArray();

    }

    public function __invoke(...$params)
    {
        $this->handle($params);
    }

    /**
     * Handle request and dispatch route.
     */
    private function handle($params)
    {
//        $this->timer = microtime();
//        $this->logger->debug('init : ' . (microtime() - $this->timer));
        try {
            $controllerName = $this->map[$params[0]];
            unset($params[0]);
            $controller = $this->dic->get($controllerName);
            if (false !== strpos($controllerName, 'Action')) {
                $request = Request::fromGlobals();
                $request = $request->withAttribute('params', $params);
                $this->response = $controller($request, new Response());
            } else {
                $method = $params[1];
                $this->response = $controller->{$method}();
            }
        } catch (\Throwable $e) {
            $this->handleErrors($e);
        }

        return $this->response;
    }

    /**
     * Handle errors and prepare response object.
     *
     * @TODO send email notification
     *
     * @param \Throwable $exception
     */
    public function handleErrors(\Throwable $exception)
    {
        $msg = $exception->getMessage();

        switch (get_class($exception)) {
            case \InvalidArgumentException::class:
//                $this->response->getBody()->write($msg);

                break;
            case \Exception::class:

                break;

            default:
                $msg = '<h3>' . $msg . '</h3>' . $exception->getTraceAsString();

                break;
        }

        $this->dic->get(Logger::class)->error($msg);
        $this->dic->get(Logger::class)->error($exception->getTraceAsString());

//        if (!defined(APPLICATION_ENV) && strtolower(getenv('APPLICATION_ENV')) === 'development') {
//            $this->response->getBody()->write('<h1>An error has occurred. </h1>' . PHP_EOL);
//            $this->response->getBody()->write($msg);
//            $this->response->getBody()->rewind();
//        }
    }

    /**
     * Sends respond back to client.
     *
     */
    public function respond()
    {
        if (!in_array($this->response->getStatusCode(), [205, 304])) {
            $body = $this->response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            while (!$body->eof()) {
                echo $body->read(2048);
                if (connection_status() != CONNECTION_NORMAL) {
                    break;
                }
            }
        }
    }
}
