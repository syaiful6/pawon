<?php

namespace Pawon\Cookie;

use Interop\Container\ContainerInterface;

class QueueMiddlewareFactory
{
    /**
     *
     */
    public function __invoke(ContainerInterface $container)
    {
        if ($container->has(QueueingCookieFactory::class)) {
            $queue = $container->get(QueueingCookieFactory::class);
        } elseif ($container->has(CookieJar::class)) {
            $queue = $container->get(CookieJar::class);
        } else {
            throw new \RuntimeException(sprintf(
                'can\'t create %s. No suitable implementation in container',
                QueueMiddleware::class
            ));
        }
        $middleware = new QueueMiddleware($queue);

        return $middleware;
    }
}
