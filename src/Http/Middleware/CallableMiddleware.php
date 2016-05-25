<?php

namespace Pawon\Http\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;

class CallableMiddleware implements MiddlewareInterface
{
    /**
     * The inner function that responsibility to handling the request
     *
     * @var callable
     */
    private $callable;

    /**
     * @param callable $callable
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * handle the request by passing it to underlying callable
     */
    public function handle(Request $request, FrameInterface $frame)
    {
        return call_user_func($this->callable, $request, $frame);
    }
}
