<?php

namespace Pawon\Database;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\ConnectionResolverInterface;

class CapsuleMiddleware
{

    protected $resolver;

    /**
     *
     */
    public function __construct(ConnectionResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     *
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ) {
        Model::clearBootedModels();
        Model::setConnectionResolver($this->resolver);
        return $next($request, $response);
    }
}
