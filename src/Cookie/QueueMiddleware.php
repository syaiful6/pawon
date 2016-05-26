<?php

namespace Pawon\Cookie;

use Pawon\Http\Middleware\FrameInterface;
use Pawon\Http\Middleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class QueueMiddleware implements MiddlewareInterface
{
    protected $cookieJar;

    /**
     *
     */
    public function __construct(QueueingCookieFactory $cookieJar)
    {
        $this->cookieJar = $cookieJar;
    }

    /**
     *
     */
    public function handle(Request $request, FrameInterface $frame)
    {
        $response = $frame->next($request);

        if ($response->hasHeader('Set-Cookie')) {
            $cookies = $response->getHeader('Set-Cookie');
        } else {
            $cookies = [];
        }

        $cookies = array_merge($cookies, $this->cookieJar->getQueuedCookies());
        if (count($cookies) > 0) {
            return $response->withHeader('Set-Cookie', $cookies);
        }

        return $response;
    }
}
