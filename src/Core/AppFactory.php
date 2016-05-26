<?php

namespace Pawon\Core;

use SplPriorityQueue;
use Zend\Expressive\Exception;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Router\FastRouteRouter;
use Pawon\Http\Exceptions\HttpException;
use Pawon\Http\ResponseFactoryInterface;
use Pawon\Http\Middleware\MiddlewarePipe;
use Pawon\Http\Middleware\CallableMiddleware;
use Pawon\Http\Middleware\MiddlewareInterface;
use Interop\Container\ContainerInterface;

class AppFactory
{
    /**
     *
     */
    public function __invoke(ContainerInterface $container = null)
    {
        $factory = $container->get(ResponseFactoryInterface::class);
        $router = $container->has(RouterInterface::class)
            ? $container->get(RouterInterface::class)
            : new FastRouteRouter();
        $finalHandler = function () {
            throw new HttpException(500, 'not handled');
        };
        $app = new Application($container, $router, $factory, $finalHandler);

        $this->injectRoutesAndPipeline($container, $app);

        return $app;
    }

    /**
     *
     */
    protected function injectRoutesAndPipeline($container, $app)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        if (isset($config['middleware_pipeline']) && is_array($config['middleware_pipeline'])) {
            $injected = $this->injectPipeline($config['middleware_pipeline'], $container, $app);
        }

        if (isset($config['routes']) && is_array($config['routes'])) {
            $this->injectRoutes($config['routes'], $app);
        }
    }

    /**
     *
     */
    private function injectPipeline(array $collections, $container, $app)
    {
        $queue = array_reduce(
            array_map($this->createCollectionMapper($container), $collections),
            $this->createPriorityQueueReducer(),
            new SplPriorityQueue()
        );

        $injections = count($queue) > 0;
        foreach ($queue as $spec) {
            $app->pipe($spec['middleware']);
        }
        return $injections;
    }

    /**
     *
     */
    private function injectRoutes(array $routes, Application $app)
    {
        foreach ($routes as $spec) {
            if (! isset($spec['path']) || ! isset($spec['middleware'])) {
                continue;
            }

            if (isset($spec['allowed_methods'])) {
                $methods = $spec['allowed_methods'];
                if (! is_array($methods)) {
                    throw new Exceptions\ImproperlyConfigured(sprintf(
                        'Allowed HTTP methods for a route must be in form of an array; received "%s"',
                        gettype($methods)
                    ));
                }
            } else {
                $methods = Route::HTTP_METHOD_ANY;
            }
            $name    = isset($spec['name']) ? $spec['name'] : null;
            $route   = new Route($spec['path'], $spec['middleware'], $methods, $name);

            if (isset($spec['options'])) {
                $options = $spec['options'];
                if (! is_array($options)) {
                    throw new Exceptions\ImproperlyConfigured(sprintf(
                        'Route options must be an array; received "%s"',
                        gettype($options)
                    ));
                }

                $route->setOptions($options);
            }

            $app->route($route);
        }
    }

    /**
     *
     */
    private function createCollectionMapper($container)
    {
        return function ($item) use ($container) {
            if (! is_array($item) || ! array_key_exists('middleware', $item)) {
                throw new Exceptions\ImproperlyConfigured(sprintf(
                    'Invalid pipeline specification received; must be an array containing a middleware '
                    . 'key, or one of the ApplicationFactory::*_MIDDLEWARE constants; received %s',
                    (is_object($item) ? get_class($item) : gettype($item))
                ));
            }

            if (! is_callable($item['middleware']) && is_array($item['middleware'])) {
                $middleware = array_map($this->mapMiddlewareStack($container), $item['middleware']);
                $item['middleware'] = new MiddlewarePipe($middleware);
            }

            return $item;
        };
    }

     /**
     *
     */
    protected function mapMiddlewareStack($container)
    {
        return function ($item) use ($container) {
            if ($item instanceof MiddlewareInterface || is_callable($item)) {
                return $item;
            } elseif (is_string($item) && $container->has($item)) {
                return new CallableMiddleware(function ($request, $frame) use ($item, $container) {
                    $md = $container->get($item);

                    return $md->handle($request, $frame);
                });
            }
            throw new Exceptions\ImproperlyConfigured(sprintf(
                'Invalid middleware detected. %s must be callable or instanceof %s',
                is_object($item) ? get_class($item) : gettype($item),
                MiddlewareInterface::class
            ));
        };
    }

    /**
     *
     */
    private function createPriorityQueueReducer()
    {
        // $serial is used to ensure that items of the same priority are enqueued
        // in the order in which they are inserted.
        $serial = PHP_INT_MAX;
        return function ($queue, $item) use (&$serial) {
            $priority = isset($item['priority']) && is_int($item['priority'])
                ? $item['priority']
                : 1;
            $queue->insert($item, [$priority, $serial--]);
            return $queue;
        };
    }
}
