<?php

namespace Pawon\Auth;

use Pawon\Http\Middleware\FrameInterface;
use Pawon\Http\Middleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * @var Pawon\Auth\Authenticator
     */
    protected $authenticator;

    /**
     *
     */
    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    /**
     *
     */
    public function handle(Request $request, FrameInterface $frame)
    {
        // need a way to lazily instantiate this user, so the session middleware not patch
        // vary header, on every request.
        $user = $this->authenticator->user();
        $request = $request->withAttribute('user', $user);

        return $frame->next($request);
    }
}
