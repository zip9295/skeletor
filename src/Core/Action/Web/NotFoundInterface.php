<?php

namespace Skeletor\Core\Action\Web;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface NotFoundInterface
{
    public function __invoke(Request $request, Response $response, $requestedRoute);
}