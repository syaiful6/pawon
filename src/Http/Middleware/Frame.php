<?php

namespace Pawon\Http\Middleware;

use SplQueue;
use Psr\Http\Message\ServerRequestInterface as Request;
use Pawon\Http\ResponseFactoryInterface as Factory;

class Frame implements FrameInterface
{
    /**
     * @var \SplQueue
     */
    protected $queue;

    /**
     * @var callable
     */
    protected $default;

    /**
     * @var Pawon\Http\ResponseFactoryInterface
     */
    protected $factory;

    /**
     * @param MiddlewareInterface[] $queue
     * @param Pawon\Http\ResponseFactoryInterface $factory
     * @param callable $default
     */
    public function __construct(SplQueue $queue, Factory $factory, callable $default)
    {
        $this->factory  = $factory;
        $this->queue    = clone $queue;
        $this->default  = $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseFactory()
    {
        return $this->factory;
    }

    /**
     * {@inheritdoc}
     */
    public function next(Request $request)
    {
        if ($this->queue->isEmpty()) {
            return call_user_func($this->default, $request);
        }
        $md = $this->queue->dequeue();
        return $md->handle($request, $this);
    }
}
