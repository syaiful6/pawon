<?php

namespace Pawon\Http\Routing;

use Pawon\Http\Exceptions\Http404;
use Zend\Expressive\Router\RouteResult;
use Pawon\Http\Middleware\FrameInterface;
use Pawon\Http\Middleware\MiddlewareInterface;
use Zend\Expressive\Router\RouterInterface as Router;
use Psr\Http\Message\ServerRequestInterface as Request;

class RoutingMiddleware implements MiddlewareInterface
{
    /**
     * @var Zend\Expressive\Router\RouterInterface
     */
    protected $router;

    /**
     *
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     *
     */
    public function handle(Request $request, FrameInterface $frame)
    {
        $result = $this->router->match($request);

        if ($result->isFailure()) {
            if ($result->isMethodFailure()) {
                $headers = [
                    'Allow' => implode(',', $result->getAllowedMethods()),
                    'Content-Type' => 'text/plain; charset=utf-8'
                ];
                $factory = $frame->getResponseFactory();
                return $factory->make('Method not allowed', 405, $headers);
            }

            throw new Http404();
        }

        // Inject the actual route result
        $request = $request->withAttribute(RouteResult::class, $result);
        return $frame->next($request);
    }
}
