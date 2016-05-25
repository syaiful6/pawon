<?php

namespace Pawon\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface TerminableMiddleware extends MiddlewareInterface
{
    /**
     * Do the work after the response send to user
     *
     * @param Psr\Http\Message\ServerRequestInterface $request
     * @param Psr\Http\Message\ServerRequestInterface $response
     * @return void
     */
    public function terminate(Request $request, Response $response);
}
