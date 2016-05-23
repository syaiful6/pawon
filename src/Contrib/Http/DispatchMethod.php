<?php

namespace Pawon\Contrib\Http;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait DispatchMethod
{
    /**
    * Dispatch the request to our class method based http method
    *
    * @param Psr\Http\Message\ServerRequestInterface $request
    * @param Psr\Http\Message\ResponseInterface $response
    * @param callable $next
    * @return Psr\Http\Message\ResponseInterface
    */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        if (method_exists($this, $method = strtolower($request->getMethod()))) {
            return $this->$method($request, $response, $next);
        }
        return $next($request, $response);
    }
}
