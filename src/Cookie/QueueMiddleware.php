<?php

namespace Pawon\Cookie;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class QueueMiddleware
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
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ) {
        $response = $next($request, $response);

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
