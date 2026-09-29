<?php

namespace Skeletor\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface MiddlewareInterface
{
    /**
     * Checks if user can access resource.
     *
     * @param ServerRequestInterface $request request
     * @param ResponseInterface $response response
     * @param null|callable $next next
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ?callable $next = null
    );

}
