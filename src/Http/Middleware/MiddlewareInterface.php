<?php

namespace Pawon\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

interface MiddlewareInterface
{
    /**
    * Handle request and return Response, if cant call Frame's
    * next method.
    *
    * @param Psr\Http\Message\ServerRequestInterface $request
    * @param Pawon\Http\Middleware\FrameInterface $frame
    * @return Psr\Http\Message\ResponseInterface
    */
    public function handle(Request $request, FrameInterface $frame);
}
