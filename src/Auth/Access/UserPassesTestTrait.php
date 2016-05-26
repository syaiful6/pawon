<?php

namespace Pawon\Auth\Access;

use Pawon\Http\Middleware\FrameInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * A trait to be used on stratigily's middleware. The actual class should replace
 * testCallback or getTestCallback. It will automatically handle if the user
 * not passed your test callback, by pipe it to middleware error or redirect it.
 * See AccessTrait for more info.
 *
 * The actual class also need to define handlePermissionPassed method, it will
 * called only the current request passed your test.
 */
trait UserPassesTestTrait
{
    use AccessTrait;

    /**
     * Define your test here against the provided request.
     *
     * @param Psr\Http\Message\ServerRequestInterfac $request
     * @return callable
     */
    protected function testCallback(Request $request)
    {
        throw new \RuntimeException('this method should implemented');
    }

    /**
     * Override this method to use another callback
     *
     * @param Psr\Http\Message\ServerRequestInterfac $request
     * @return callable
     */
    protected function getTestCallback(Request $request)
    {
        return $this->testCallback($request);
    }

    /**
     *
     */
    public function handle(Request $request, FrameInterface $frame)
    {
        $callback = $this->getTestCallback($request);
        if (!$callback()) {
            return $this->handleNoPermission($request, $frame);
        }

        return $this->handlePermissionPassed($request, $frame);
    }
}
