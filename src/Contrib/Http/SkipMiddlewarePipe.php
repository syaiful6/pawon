<?php

namespace Pawon\Contrib\Http;

use Interop\Container\ContainerInterface;
use Pawon\Http\Middleware\FrameInterface;
use Pawon\Http\Middleware\MiddlewareInterface;
use Pawon\Http\Middleware\MiddlewarePipe;
use Pawon\Http\Middleware\CallableMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * This middleware used to disable our common web middleware stack when the request
 * matched for the given skipPath. This is usefull if the stack is common but only
 * need to dissable only for a few route. We use this to dissable our web middleware
 * on api route.
 */
class SkipMiddlewarePipe extends MiddlewarePipe
{
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
    public function handle(Request $request, FrameInterface $frame) {
        $path = $request->getUri()->getPath() ?: '/';
        $route = $this->skipPath;
        $normalizedRoute = (strlen($route) > 1) ? rtrim($route, '/') : $route;

        // not match with the skip path, so we can pipe this stack
        if (substr(strtolower($path), 0, strlen($normalizedRoute)) !== strtolower($normalizedRoute)) {
            return parent::handle($request, $frame);
        }
        // if match is not at a border ('/', '.', or end), we can process em
        $border = $this->getBorder($path, $normalizedRoute);
        if ($border && '/' !== $border && '.' !== $border) {
            return parent::handle($request, $frame);
        }

        // this current path match again skip path so process the next
        return $frame->next($request);
    }

    /**
     *
     */
    public function pipe($middleware)
    {
        $middleware = $this->normalize($middleware);
        parent::pipe($middleware);
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

    /**
     *
     */
    private function normalize($middleware)
    {
        if (is_callable($middleware) || $midlleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        if (is_array($middleware)) {
            return $this->resolveMiddlewarePipe($middleware);
        }

        if (is_string($middleware) && $this->container->has($middleware)) {
            return $this->resolveLazyMiddlewareService($middleware);
        }

        throw new \RuntimeException('Invalid middleware detected');
    }

    /**
     *
     */
    private function resolveMiddlewarePipe($middlewares)
    {
        $middlewarePipe = new MiddlewarePipe();

        foreach ($middlewares as $middleware) {
            $middlewarePipe->pipe(
                $this->resolve($middleware)
            );
        }

        return $middlewarePipe;
    }

    /**
     * @param string $middleware
     * @param ContainerInterface $container
     * @return callable
     */
    private function resolveLazyMiddlewareService($middleware)
    {
        return new CallableMiddleware(function ($request, $frame) use ($middleware) {
            $md = $this->container->get($middleware);
            if ($md instance MiddlewareInterface) {
                throw new \InvalidMiddlewareException(sprintf(
                    'Lazy-loaded middleware "%s" is not instance of %s',
                    $middleware,
                    MiddlewareInterface::class
                ));
            }
            return $md->handle($request, $frame);
        });
    }
}
