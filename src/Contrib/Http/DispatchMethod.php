<?php

namespace Pawon\Contrib\Http;

use Pawon\Http\Middleware\FrameInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

trait DispatchMethod
{
    /**
     *
     */
    public function handle(Request $request, FrameInterface $frame)
    {
        if (method_exists($this, $method = strtolower($request->getMethod()))) {
            return $this->$method($request, $frame);
        }

        return $frame->next($request);
    }
}
