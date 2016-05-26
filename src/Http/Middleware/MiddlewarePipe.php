<?php

namespace Pawon\Http\Middleware;

use SplQueue;
use Pawon\Http\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MiddlewarePipe implements MiddlewareInterface
{
    protected $queue;


    /**
     *
     */
    public function __construct(array $md = null)
    {
        $this->queue = new SplQueue();
        if ($md !== null) {
            foreach ($md as $m) {
                $this->pipe($m);
            }
        }
    }

    /**
     *
     */
    public function pipe($md)
    {
        $this->queue->enqueue($this->normalizeMiddleware($md));
    }

    /**
     *
     */
    public function handle(Request $request, FrameInterface $frame)
    {
        // if we exhausted then call the next middleware
        $default = function ($req) use ($frame) {
            return $frame->next($req);
        };

        $inner = new Frame($this->queue, $frame->getResponseFactory(), $default);

        return $inner->next($request);
    }

    /**
     *
     */
    protected function normalizeMiddleware($middleware)
    {
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        } elseif (is_callable($middleware)) {
            return new CallableMiddleware($middleware);
        }
        throw new \InvalidArgumentException(sprintf(
            'Invalid middleware detected. %s must be callable or instanceof %s',
            is_object($middleware) ? get_class($middleware) : gettype($middleware),
            MiddlewareInterface::class
        ));
    }
}
