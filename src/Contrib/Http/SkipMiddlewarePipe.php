<?php

namespace Pawon\Contrib\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Container\ContainerInterface;
use Zend\Expressive\MarshalMiddlewareTrait;
use Zend\Stratigility\MiddlewarePipe;
use Zend\Stratigility\FinalHandler;

/**
 * This middleware used to disable our common web middleware stack when the request
 * matched for the given skipPath. This is usefull if the stack is common but only
 * need to dissable only for a few route. We use this to dissable our web middleware
 * on api route.
 */
class SkipMiddlewarePipe extends MiddlewarePipe
{
    use MarshalMiddlewareTrait;

    /**
     * @var \Interop\Container\ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $skipPath;

    /**
     *
     */
    public function __construct(ContainerInterface $container, $skipPath = '/api')
    {
        parent::__construct();
        $this->skipPath = $skipPath;
        $this->container = $container;
    }

    /**
     *
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    ) {
        $path = $request->getUri()->getPath() ?: '/';
        $route = $this->skipPath;
        $normalizedRoute = (strlen($route) > 1) ? rtrim($route, '/') : $route;

        $next = $next ?: new FinalHandler([], $response);
        // not match with the skip path, so we can pipe this stack
        if (substr(strtolower($path), 0, strlen($normalizedRoute)) !== strtolower($normalizedRoute)) {
            return parent::__invoke($request, $response, $next);
        }
        // if match is not at a border ('/', '.', or end), we can process em
        $border = $this->getBorder($path, $normalizedRoute);
        if ($border && '/' !== $border && '.' !== $border) {
            return parent::__invoke($request, $response, $next);
        }

        // this current path match again skip path so process the next
        return $next($request, $response);
    }

    /**
     *
     */
    public function pipe($path, $middleware = null)
    {
        if (null === $middleware) {
            $middleware = $this->prepareMiddleware($path, $this->container);
            $path = '/';
        }

        if (! is_callable($middleware)
            && (is_string($middleware) || is_array($middleware))
        ) {
            $middleware = $this->prepareMiddleware($middleware, $this->container);
        }

        parent::pipe($path, $middleware);

        return $this;
    }

     /**
     * Determine the border between the request path and current route
     *
     * @param string $path
     * @param string $route
     * @return string
     */
    protected function getBorder($path, $route)
    {
        if ($route === '/') {
            return '/';
        }
        $routeLength = strlen($route);
        return (strlen($path) > $routeLength) ? $path[$routeLength] : '';
    }
}
