<?php

namespace Pawon\Tests\Cookie;

use Mockery as m;
use SplQueue;
use PHPUnit_Framework_TestCase;
use Pawon\Http\Middleware\Frame;
use Pawon\Http\ResponseFactoryInterface;
use Pawon\Http\Middleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\Response as Response;

class FrameTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /**
     *
     */
    public function testFrameCall()
    {
        $request = m::mock(Request::class);
        $factory = m::mock(ResponseFactoryInterface::class);
        $middleware = m::mock(MiddlewareInterface::class)
            ->shouldReceive('handle')
            ->andReturn(m::mock(Response::class))
            ->once()
            ->mock();

        $queue = new SplQueue();
        $queue->enqueue($middleware);

        $frame = new Frame($queue, $factory, function () {});
        $res = $frame->next($request);

        $this->assertTrue($res instanceof Response);
    }

    /**
     *
     */
    public function testCallFactory()
    {
        $factory = m::mock(ResponseFactoryInterface::class)
            ->shouldReceive('make')
            ->once()
            ->mock();

        $frame = new Frame(new SplQueue(), $factory, function () {});
        $actual = $frame->getResponseFactory();
        $this->assertTrue($actual instanceof ResponseFactoryInterface);
        // try to call it
        $actual->make();
    }

    /**
     *
     */
    public function testDefaultHandlerCalledWhenEmpty()
    {
        $factory = m::mock(ResponseFactoryInterface::class);
        $tmp = null;
        $default = function ($request) use (&$tmp) {
            $tmp = true;
            $this->assertTrue($request instanceof Request);
        };
        $frame = new Frame(new SplQueue(), $factory, $default);
        $request = m::mock(Request::class);
        $frame->next($request);
        $this->assertNotNull($tmp);
    }
}
