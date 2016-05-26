<?php

namespace Pawon\Http\Routing;

use Interop\Container\ContainerInterface;
use Zend\Expressive\Router\RouterInterface as Router;

class RoutingServiceFactory
{
    /**
     *
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $name = str_replace(__NAMESPACE__, '', $requestedName);
        if ($name[0] === '\\') {
            $name = substr($name, 1);
        }
        if (method_exists($this, "create$name")) {
            return call_user_func([$this, "create$name"], $container);
        } else {
            throw new \RuntimeException("can\'t create $requestedName");
        }
    }

    /**
     *
     */
    private function createRoutedMiddlewareResolver($container)
    {
        return new ContainerRoutedMiddlewareResolver($container);
    }

    /**
     *
     */
    private function RoutingMiddleware($container)
    {
        return new RoutingMiddleware($container->get(Router::class));
    }

    /**
     *
     */
    private function createDispatchRoute($container)
    {
        return new DispatchRoute(
            $container->get(RoutedMiddlewareResolver::class)
        );
    }
}
