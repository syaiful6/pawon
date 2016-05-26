<?php

namespace Pawon\Http\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;

interface FrameInterface
{
    /**
     * Call next middleware on the stack. And return response
     *
     * @param Psr\Http\Message\ServerRequestInterface
     * @return Psr\Http\Message\ResponseInterface
     */
    public function next(Request $request);

    /**
     * @return Pawon\Http\ResponseInterface
     */
    public function getResponseFactory();
}
