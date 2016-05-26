<?php

namespace Pawon\Http\Routing;

use Pawon\Http\Middleware\MiddlewareInterface;
use Pawon\Http\Middleware\MiddlewarePipe;
use Pawon\Http\Middleware\CallableMiddleware;
use Interop\Container\ContainerInterface;

class ContainerRoutedMiddlewareResolver implements RoutedMiddlewareResolver
{
	protected $container;

	/**
	 *
	 */
	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}

    /**
     *
     */
    public function resolve($middleware)
    {
        if (is_callable($middleware)) {
            return new CallableMiddleware($middleware);
        }

        if ($middleware instanceof MiddlewareInterface) {
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
            if (!$md instanceof MiddlewareInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'Lazy-loaded middleware "%s" is not instance of %s',
                    $middleware,
                    MiddlewareInterface::class
                ));
            }
            return $md->handle($request, $frame);
        });
    }
}
