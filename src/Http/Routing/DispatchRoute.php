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
    public function __construct(RoutedMiddlewareResolver $resolver)
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
        $inner = $this->resolver->resolve($sign);

        foreach ($result->getMatchedParams() as $param => $value) {
            $request = $request->withAttribute($param, $value);
        }
        return $inner->handle($request, $frame);
    }
}
