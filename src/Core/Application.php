<?php

namespace Pawon\Core;

use Pawon\Http\Middleware\MiddlewarePipe;
use Interop\Container\ContainerInterface;
use Pawon\Http\ResponseFactoryInterface;
use Pawon\Http\Middleware\Frame;
use Pawon\Http\Middleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouterInterface;
use function Itertools\zip;
use function Itertools\to_array;

class Application extends MiddlewarePipe
{
    /**
     * @var Interop\Container\ContainerInterface
     */
    protected $container;

    /**
     * @var Pawon\Http\ResponseFactoryInterface
     */
    protected $factory;

    /**
     * @var callable
     */
    protected $finalHandler;

    /**
     * @var Zend\Expressive\Router\RouterInterface
     */
    private $router;

    /**
     * @var string[] HTTP methods that can be used for routing
     */
    private $httpRouteMethods = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
    ];

    /**
     *
     */
    private $routes = [];

    /**
     *
     */
    public function __construct(
        ContainerInterface $container,
        RouterInterface $router,
        ResponseFactoryInterface $factory,
        callable $finalHandler
    ) {
        $this->router = $router;
        $this->container = $container;
        $this->factory = $factory;
        $this->finalHandler = $finalHandler;
        parent::__construct();
    }

    /**
     *
     */
    public function route($path, $middleware = null, array $methods = null, $name = null)
    {
        if (!$path instanceof Route && null === $middleware) {
            throw new Exception\ImproperlyConfigured(sprintf(
                '%s expects either a route argument, or a combination of a path and middleware arguments',
                __METHOD__
            ));
        }

        if ($path instanceof Route) {
            $route = $path;
            $path = $route->getPath();
            $methods = $route->getAllowedMethods();
            $name = $route->getName();
        }

        $this->checkForDuplicateRoute($path, $methods);

        if (!isset($route)) {
            $methods = (null === $methods) ? Route::HTTP_METHOD_ANY : $methods;
            $route = new Route($path, $middleware, $methods, $name);
        }

        $this->routes[] = $route;
        $this->router->addRoute($route);

        return $route;
    }

    /**
     *
     */
    public function pipe($middleware)
    {
        $middleware = $this->prepareMiddleware($middleware);
        parent::pipe($middleware);
    }

    /**
     *
     */
    public function __invoke(Request $request, callable $startResponse)
    {
        $frame = new Frame($this->queue, $this->factory, $this->finalHandler);
        $response = $frame->next($request);
        // complain if the stack not return reponse (common error)
        if (!$response instanceof Response) {
            throw new \UnexpectedValueException(sprintf(
                'The middleware stack end with non instance of %s, it return %s instead.',
                Response::class,
                is_object($response) ? get_class($response) : gettype($response)
            ));
        }
        $status = sprintf(
            '%d %s',
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
        $headers = $response->getHeaders();
        $headers = zip(array_keys($headers), array_values($headers));
        // start!
        $startResponse($status, to_array($headers));

        return $response;
    }

    /**
     *
     */
    protected function prepareMiddleware($middleware)
    {
        if (is_callable($middleware) || $middleware instanceof MiddlewareInterface) {
            return $middleware;
        } elseif (is_string($middleware) && $this->container->has($middleware)) {
            return function ($request, $frame) use ($middleware) {
                $md = $this->container->get($middleware);

                return $md->handle($request, $frame);
            };
        } elseif (is_array($middleware)) {
            return $this->preparePipeMiddleware($middleware);
        }

        throw new Exceptions\ImproperlyConfigured(sprintf(
            'Invalid middleware detected. %s must be callable or instanceof %s',
            is_object($middleware) ? get_class($middleware) : gettype($middleware),
            MiddlewareInterface::class
        ));
    }

    /**
     *
     */
    private function preparePipeMiddleware($middlewares)
    {
        $middlewarePipe = new MiddlewarePipe();

        foreach ($middlewares as $middleware) {
            $middlewarePipe->pipe(
                $this->prepareMiddleware($middleware)
            );
        }

        return $middlewarePipe;
    }

    /**
     *
     */
    private function checkForDuplicateRoute($path, $methods = null)
    {
        if (null === $methods) {
            $methods = Route::HTTP_METHOD_ANY;
        }

        $matches = array_filter($this->routes, function (Route $route) use ($path, $methods) {
            if ($path !== $route->getPath()) {
                return false;
            }

            if ($methods === Route::HTTP_METHOD_ANY) {
                return true;
            }

            return array_reduce($methods, function ($carry, $method) use ($route) {
                return $carry || $route->allowsMethod($method);
            }, false);
        });

        if (count($matches) > 0) {
            throw new Exception\ImproperlyConfigured(
                'Duplicate route detected; same name or path, and one or more HTTP methods intersect'
            );
        }
    }
}
