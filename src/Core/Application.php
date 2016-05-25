<?php

namespace Pawon\Core;

use SplQueue;
use Traversable;
use Interop\Container\ContainerInterface;
use Pawon\Http\ResponseFactoryInterface;
use Pawon\Http\Middleware\Frame;
use Pawon\Http\Middleware\CallableMiddleware;
use Pawon\Http\Middleware\MiddlewareInterface;
use Pawon\Http\Middleware\TerminableMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Router\RouterInterface;
use function Itertools\zip;
use function Itertools\to_array;

class Application
{
    /**
     * @var Pawon\Http\ResponseFactoryInterface
     */
    protected $factory;

    /**
     * @var
     */
    protected $finalHandler;

    /**
     *
     */
    protected $queue;

    /**
     *
     */
    protected $terminable;

    /**
     *
     */
    public function __construct(
        ResponseFactoryInterface $factory,
        callable $finalHandler = null
    ) {

        $this->queue = new SplQueue();
        $this->factory = $factory;
        $this->finalHandler = $finalHandler;
    }

    /**
     *
     */
    public function pipe($middleware)
    {
        $this->queue->dequeue($this->normalizeMiddleware($middleware));
    }

    /**
     *
     */
    public function __invoke(ServerRequestInterface $request, callable $startResponse)
    {
        $frame = new Frame($this->queue, $this->factory, $this->finalHandler);
        $response = $frame->next($request);

        $status = sprintf(
           '%d %s',
           $response->getStatusCode(),
           $response->getReasonPhrase()
        );
        $headers = $response->getHeaders();
        $headers = zip(array_keys($headers), array_values($headers));
        // start!
        $startResponse($status, to_array($headers));

        return $response;
    }

    /**
     *
     */
    protected function normalizeMiddleware($middleware)
    {
        if ($middleware instanceof MiddlewareInterface) {
            if ($middleware instanceof TerminableMiddleware) {
                $this->terminable[] = $middleware;
            }
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
