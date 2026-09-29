<?php
namespace Skeletor\Core\App;

use DI\Container;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest as Request;
use Skeletor\Core\Config\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Cache\Service\FullPageCache;
use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Core\Login\Controller\LoginController;

class WebSkeletor
{
    private Container $dic;

    private ResponseInterface $response;

    private LoggerInterface $logger;

    private string $uri;

    public function __construct(Container $dic, Logger $logger)
    {
        $this->dic = $dic;
        $this->response = new Response();
        $this->logger = $logger;
        $this->handle();
    }

    private function malformedRequest(): void
    {
        $banPatterns = [
            '/^\/\/.*\.php7$/', // matches: //*.php7
            '/^\/\/\..*$/', // matches: //.*, but also matches //index.jpg
            '/^.*\.php$/', // matches: *.php // could even block access to .php files from Web
        ];
        $skipUris = [
            'cdn.js',
        ];
        $requestedUri = str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
        $foundBan = array_map(function ($pattern) use ($requestedUri) {
            preg_match($pattern, $requestedUri, $matches);
            if (count($matches)) {
                return true;
            }
            return false;
        }, $banPatterns);
        // @TODO add some ban mechanism

        $foundSkip = array_map(function ($uri) use ($requestedUri) {
            if (str_contains($requestedUri, $uri)) {
                return true;
            }
            return false;
        }, $skipUris);
        $found = array_filter(array_merge($foundSkip, $foundBan), static function($var){return $var !== null;} );
        if (count($found)) {
            header('Location: ' . $this->dic->get(Config::class)->baseUrl);
            exit();
        }
    }

    /**
     * Handle request and dispatch route.
     */
    private function handle(): void
    {
        $dispatcher = $this->dic->get(\FastRoute\Dispatcher::class);
        $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (!$url) {
            $this->malformedRequest();
        }
        $ignoreTrailingSlash = $this->dic->get(Config::class)->get('ignoreTrailingSlash');
        $this->uri = rawurldecode($url);
        $route = $dispatcher->dispatch(
            $_SERVER['REQUEST_METHOD'],
            $this->uri
        );

        // If not found and ignoreTrailingSlash is enabled, try the opposite slash variant
        if ($ignoreTrailingSlash && $route[0] === \FastRoute\Dispatcher::NOT_FOUND && $url !== '/') {
            $alt = str_ends_with($url, '/') ? rtrim($url, '/') : $url . '/';
            $route = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $alt);
        }

        $request = Request::fromGlobals();

        switch ($route[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                $this->handle404($request, $this->response, $_SERVER['REQUEST_URI']);
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $this->response->getBody()->write('Requested method %s is not allowed.');
                break;
            case \FastRoute\Dispatcher::FOUND:
                $controller = $route[1];
                $parameters = $route[2];

                // @TODO this must be better
                foreach ($parameters as $name => $value) {
                    $request = $request->withAttribute($name, $value);
                }

                try {
                    if (is_array($controller)) {
                        $method = $controller[1];
                        $class = $controller[0];
                        $instance = $this->dic->get($class);
                        // Set request/response so controller methods work
                        // even without going through __invoke()
                        if (method_exists($instance, 'setRequest')) {
                            $instance->setRequest($request);
                        }
                        $next = [$instance, $method];
                    } else {
                        $next = $this->dic->get($controller);
                    }

                    if ($this->dic->has(\Skeletor\Core\Middleware\MiddlewareInterface::class)) {
                        $this->response = $this->dic->call(\Skeletor\Core\Middleware\MiddlewareInterface::class, [
                            $request, $this->response, $next
                        ]);
                    } else {
                        $this->response = $this->dic->call($next, [$request, $this->response]);
                    }

                } catch (NotFoundException $e) {
                    $this->handle404($request, $this->response, $_SERVER['REQUEST_URI']);
                } catch (\Throwable $e) {
                    $this->handleErrors($e);
                }
                break;
        }
    }

    private function handle404(Request $request, Response $response, $requestedRoute): void
    {
        $notFound = $this->dic->get(\Skeletor\Core\Action\Web\NotFoundInterface::class);
        $this->response = $notFound($request, $response, $requestedRoute);
    }

    /**
     * Handle errors and prepare response object.
     *
     * @TODO send email notification
     *
     * @param \Throwable $exception
     */
    public function handleErrors(\Throwable $exception): void
    {
        $msg = $exception->getMessage();

        switch (get_class($exception)) {
            case \InvalidArgumentException::class:
//                $this->response->getBody()->write($msg);
                break;
            default:
                $msg = '<h3>' . $msg . '</h3>' . $exception->getTraceAsString();

                break;
        }

        $this->dic->get(Logger::class)->error($msg, ['trace' => $exception->getTraceAsString()]);

        $env = getenv('APPLICATION_ENV');
        if ($env && strtolower(getenv('APPLICATION_ENV')) !== 'production') {
            $this->response->getBody()->write('<h1>An error has occurred. </h1>' . PHP_EOL);
            $this->response->getBody()->write($msg);
        }
    }

    /**
     * Sends respond back to client.
     *
     */
    public function respond(): void
    {
        // Send response
        if (!headers_sent()) {
            // Status
            header(sprintf(
                'HTTP/%s %s %s',
                $this->response->getProtocolVersion(),
                $this->response->getStatusCode(),
                $this->response->getReasonPhrase()
            ));

            // Headers
            foreach ($this->response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        // Send Body
        if (!in_array($this->response->getStatusCode(), [205, 304])) {
            $body = $this->response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $chunkSize = 4096;
            $contentLength  = $this->response->getHeaderLine('Content-Length');
            if (!$contentLength) {
                $contentLength = $body->getSize();
            }

            $cacheActive = false;
            if ($this->dic->has('cachedPages') && strtolower($_SERVER['REQUEST_METHOD']) === 'get' && strlen($_SERVER['QUERY_STRING']) === 0) {
                foreach ($this->dic->get('cachedPages') as $uri) {
                    if ($uri === '/') {
                        $parts = explode('/', $this->uri);
                        // one slug part
                        if (count($parts) === 2) {
                            $cacheActive = true;
                            break;
                        }
                    }
                    if (false !== strpos($uri, '*')) { // wildcard
                        if (false !== strpos($this->uri, rtrim($uri, '*'))) {
                            $cacheActive = true;
                            break;
                        }
                    } elseif ($this->uri === $uri) {
                        $cacheActive = true;
                        break;
                    }
                }
            }
            if ($cacheActive) {
                ob_start();
            }
            if (isset($contentLength)) {
                $amountToRead = $contentLength;
                while ($amountToRead > 0 && !$body->eof()) {
                    $data = $body->read(min($chunkSize, $amountToRead));
                    echo $data;
                    $amountToRead -= strlen($data);

                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            } else {
                while (!$body->eof()) {
                    echo $body->read($chunkSize);
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            }

            if ($cacheActive) {
                $html = ob_get_clean();
                if (class_exists(FullPageCache::class)) {
                    $cacheFolder = FullPageCache::FULL_PAGE_CACHE_PATH . $this->uri;
                    if (!is_dir($cacheFolder)) {
                        mkdir($cacheFolder);
                    }
                    echo $html;
                    file_put_contents($cacheFolder . '/index.html', $html);
                } else {
                    echo $html;
                }
            }

        }
    }
}
