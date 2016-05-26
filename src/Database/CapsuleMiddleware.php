<?php

namespace Pawon\Database;

use Illuminate\Database\Eloquent\Model;
use Pawon\Http\Middleware\FrameInterface;
use Pawon\Http\Middleware\MiddlewareInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class CapsuleMiddleware implements MiddlewareInterface
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
    public function handle(Request $request, FrameInterface $frame)
    {
        Model::clearBootedModels();
        Model::setConnectionResolver($this->resolver);
        return $frame->next($request);
    }
}
