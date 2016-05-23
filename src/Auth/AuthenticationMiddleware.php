<?php

namespace Pawon\Auth;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class AuthenticationMiddleware
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
    public function __invoke(Request $request, Response $response, callable $next)
    {
        // need a way to lazily instantiate this user, so the session middleware not patch
        // vary header, on every request.
        $user = $this->authenticator->user();
        $request = $request->withAttribute('user', $user);

        return $next($request, $response);
    }
}
