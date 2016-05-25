<?php

namespace Pawon\Http\Routing;

use Zend\Expressive\Router\RouteResult;
use Pawon\Http\Middleware\FrameInterface;
use Pawon\Http\Middleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class DispatchRoute implements MiddlewareInterface
{
    /**
     * @var callable $resolver
     */
    private $resolver;

    /**
     *
     */
    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     *
     */
    public function handle(Request $request, FrameInterface $frame)
    {
        $result = $request->getAttribute(RouteResult::class);
        if (! $result) {
            return $frame->next($request);
        }

        $sign = $result->getMatchedMiddleware();
        $params = $result->getMatchedParams();

        $inner = call_user_func($this->resolver, $sign);

        array_unshift($params, $request);

        return $inner(...$params);
    }
}
