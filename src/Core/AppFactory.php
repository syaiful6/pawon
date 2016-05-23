<?php

namespace Pawon\Core;

use SplPriorityQueue;
use Zend\Expressive\Exception;
use Interop\Container\ContainerInterface;
use Zend\Expressive\Container\Exception\InvalidArgumentException as ContainerInvalidArgumentException;
use Zend\Expressive\Router\FastRouteRouter;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouterInterface;
use Zend\Stratigility\MiddlewarePipe;

class AppFactory
{
    const DISPATCH_MIDDLEWARE = 'EXPRESSIVE_DISPATCH_MIDDLEWARE';
    const ROUTING_MIDDLEWARE = 'EXPRESSIVE_ROUTING_MIDDLEWARE';

    /**
     * @deprecated This constant will be removed in v1.1.
     */
    const ROUTE_RESULT_OBSERVER_MIDDLEWARE = 'EXPRESSIVE_ROUTE_RESULT_OBSERVER_MIDDLEWARE';

    /**
     *
     */
    public function __invoke(ContainerInterface $container = null)
    {
        $router = $container->has(RouterInterface::class)
            ? $container->get(RouterInterface::class)
            : new FastRouteRouter();

        $finalHandler = $container->has('Zend\Expressive\FinalHandler')
            ? $container->get('Zend\Expressive\FinalHandler')
            : null;

        $app = new Application($router, $container, $finalHandler);

        $this->injectRoutesAndPipeline($app, $container);

        return $app;
    }

    /**
     * Injects routes and the middleware pipeline into the application.
     *
     * @param Application $app
     * @param ContainerInterface $container
     */
    protected function injectRoutesAndPipeline(Application $app, ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $pipelineCreated = false;

        if (isset($config['middleware_pipeline']) && is_array($config['middleware_pipeline'])) {
            $pipelineCreated = $this->injectPipeline($config['middleware_pipeline'], $app);
        }

        if (isset($config['routes']) && is_array($config['routes'])) {
            $this->injectRoutes($config['routes'], $app);

            if (! $pipelineCreated) {
                $app->pipeRoutingMiddleware();
                $app->pipeDispatchMiddleware();
            }
        }
    }

    /**
     * Inject the middleware pipeline
     *
     * This method injects the middleware pipeline.
     *
     * If the pre-RC6 pre_/post_routing keys exist, it raises a deprecation
     * notice, and then builds the pipeline based on that configuration
     * (though it will raise an exception if other keys are *also* present).
     *
     * Otherwise, it passes the pipeline on to `injectMiddleware()`,
     * returning a boolean value based on whether or not any
     * middleware was injected.
     *
     * @deprecated This method will be removed in v1.1.
     * @param array $pipeline
     * @param Application $app
     * @return bool
     */
    protected function injectPipeline(array $pipeline, Application $app)
    {
        $deprecatedKeys = $this->getDeprecatedKeys(array_keys($pipeline));
        if (! empty($deprecatedKeys)) {
            $this->handleDeprecatedPipeline($deprecatedKeys, $pipeline, $app);
            return true;
        }

        return $this->injectMiddleware($pipeline, $app);
    }

    /**
     * Retrieve a list of deprecated keys from the pipeline, if any.
     *
     * @deprecated This method will be removed in v1.1.
     * @param array $pipelineKeys
     * @return array
     */
    protected function getDeprecatedKeys(array $pipelineKeys)
    {
        return array_intersect(['pre_routing', 'post_routing'], $pipelineKeys);
    }

    /**
     * Handle deprecated pre_/post_routing configuration.
     *
     * @deprecated This method will be removed in v1.1.
     * @param array $deprecatedKeys The list of deprecated keys present in the
     *     pipeline
     * @param array $pipeline
     * @param Application $app
     * @return void
     * @throws ContainerInvalidArgumentException if $pipeline contains more than
     *     just pre_ and/or post_routing keys.
     * @throws ContainerInvalidArgumentException if the pre_routing configuration,
     *     if present, is not an array
     * @throws ContainerInvalidArgumentException if the post_routing configuration,
     *     if present, is not an array
     */
    protected function handleDeprecatedPipeline(array $deprecatedKeys, array $pipeline, Application $app)
    {
        if (count($deprecatedKeys) < count($pipeline)) {
            throw new ContainerInvalidArgumentException(
                'middleware_pipeline cannot contain a mix of middleware AND pre_/post_routing keys; '
                . 'please update your configuration to define middleware_pipeline as a single pipeline; '
                . 'see https://zendframework.github.io/zend-expressive/reference/migration/rc-to-v1/'
            );
        }

        trigger_error(
            'pre_routing and post_routing configuration is deprecated; '
            . 'update your configuration to define the middleware_pipeline as a single pipeline; '
            . 'see https://zendframework.github.io/zend-expressive/reference/migration/rc-to-v1/',
            E_USER_DEPRECATED
        );

        if (isset($pipeline['pre_routing'])) {
            if (! is_array($pipeline['pre_routing'])) {
                throw new ContainerInvalidArgumentException(sprintf(
                    'Pre-routing middleware collection must be an array; received "%s"',
                    gettype($pipeline['pre_routing'])
                ));
            }
            $this->injectMiddleware($pipeline['pre_routing'], $app);
        }

        $app->pipeRoutingMiddleware();
        $app->pipeRouteResultObserverMiddleware();
        $app->pipeDispatchMiddleware();

        if (isset($pipeline['post_routing'])) {
            if (! is_array($pipeline['post_routing'])) {
                throw new ContainerInvalidArgumentException(sprintf(
                    'Post-routing middleware collection must be an array; received "%s"',
                    gettype($pipeline['post_routing'])
                ));
            }
            $this->injectMiddleware($pipeline['post_routing'], $app);
        }
    }

    /**
     * Inject routes from configuration, if any.
     *
     * @param array $routes Route definitions
     * @param Application $app
     */
    protected function injectRoutes(array $routes, Application $app)
    {
        foreach ($routes as $spec) {
            if (! isset($spec['path']) || ! isset($spec['middleware'])) {
                continue;
            }

            if (isset($spec['allowed_methods'])) {
                $methods = $spec['allowed_methods'];
                if (! is_array($methods)) {
                    throw new ContainerInvalidArgumentException(sprintf(
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
                    throw new ContainerInvalidArgumentException(sprintf(
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
     * Given a collection of middleware specifications, pipe them to the application.
     *
     * @param array $collection
     * @param Application $app
     * @return bool Flag indicating whether or not any middleware was injected.
     * @throws Exception\InvalidMiddlewareException for invalid middleware.
     */
    protected function injectMiddleware(array $collection, Application $app)
    {
        // Create a priority queue from the specifications
        $queue = array_reduce(
            array_map($this->createCollectionMapper($app), $collection),
            $this->createPriorityQueueReducer(),
            new SplPriorityQueue()
        );

        $injections = count($queue) > 0;

        foreach ($queue as $spec) {
            $path  = isset($spec['path']) ? $spec['path'] : '/';
            $error = array_key_exists('error', $spec) ? (bool) $spec['error'] : false;
            $pipe  = $error ? 'pipeErrorHandler' : 'pipe';

            $app->{$pipe}($path, $spec['middleware']);
        }

        return $injections;
    }

    /**
     * Create and return the pipeline map callback.
     *
     * The returned callback has the signature:
     *
     * <code>
     * function ($item) : callable|string
     * </code>
     *
     * It is suitable for mapping pipeline middleware representing the application
     * routing o dispatching middleware to a callable; if the provided item does not
     * match either, the item is returned verbatim.
     *
     * @todo Remove ROUTE_RESULT_OBSERVER_MIDDLEWARE detection for 1.1
     * @param Application $app
     * @return callable
     */
    protected function createPipelineMapper(Application $app)
    {
        return function ($item) use ($app) {
            if ($item === self::ROUTING_MIDDLEWARE) {
                return [$app, 'routeMiddleware'];
            }

            if ($item === self::DISPATCH_MIDDLEWARE) {
                return [$app, 'dispatchMiddleware'];
            }

            if ($item === self::ROUTE_RESULT_OBSERVER_MIDDLEWARE) {
                $r = new \ReflectionProperty($app, 'routeResultObserverMiddlewareIsRegistered');
                $r->setAccessible(true);
                $r->setValue($app, true);
                return [$app, 'routeResultObserverMiddleware'];
            }

            return $item;
        };
    }

    /**
     * Create the collection mapping function.
     *
     * Returns a callable with the following signature:
     *
     * <code>
     * function (array|string $item) : array
     * </code>
     *
     * When it encounters one of the self::*_MIDDLEWARE constants, it passes
     * the value to the `createPipelineMapper()` callback to create a spec
     * that uses the return value as pipeline middleware.
     *
     * If the 'middleware' value is an array, it uses the `createPipelineMapper()`
     * callback as an array mapper in order to ensure the self::*_MIDDLEWARE
     * are injected correctly.
     *
     * If the 'middleware' value is missing, or not viable as middleware, it
     * raises an exception, to ensure the pipeline is built correctly.
     *
     * @param Application $app
     * @return callable
     */
    protected function createCollectionMapper(Application $app)
    {
        $pipelineMap = $this->createPipelineMapper($app);
        $appMiddlewares = [
            self::ROUTING_MIDDLEWARE,
            self::DISPATCH_MIDDLEWARE,
            self::ROUTE_RESULT_OBSERVER_MIDDLEWARE
        ];

        return function ($item) use ($app, $pipelineMap, $appMiddlewares) {
            if (in_array($item, $appMiddlewares, true)) {
                return ['middleware' => $pipelineMap($item)];
            }

            if (! is_array($item) || ! array_key_exists('middleware', $item)) {
                throw new ContainerInvalidArgumentException(sprintf(
                    'Invalid pipeline specification received; must be an array containing a middleware '
                    . 'key, or one of the ApplicationFactory::*_MIDDLEWARE constants; received %s',
                    (is_object($item) ? get_class($item) : gettype($item))
                ));
            }

            if (! is_callable($item['middleware']) && is_array($item['middleware'])) {
                $item['middleware'] = array_map($pipelineMap, $item['middleware']);
            }

            return $item;
        };
    }

    /**
     * Create reducer function that will reduce an array to a priority queue.
     *
     * Creates and returns a function with the signature:
     *
     * <code>
     * function (SplQueue $queue, array $item) : SplQueue
     * </code>
     *
     * The function is useful to reduce an array of pipeline middleware to a
     * priority queue.
     *
     * @return callable
     */
    protected function createPriorityQueueReducer()
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
