<?php

namespace Pawon\Session;

use Interop\Container\ContainerInterface;
use Pawon\Core\Exceptions\ImproperlyConfigured;

class SessionMiddlewareFactory
{
    /**
     *
     */
    public function __invoke(ContainerInterface $container)
    {
        if (!$container->has(Store::class)) {
            throw new ImproperlyConfigured(sprintf(
                '%s not configured on container. Can\'t create Session Middleware'
            ));
        }
        $store = $container->get(Store::class);
        $config = $container->has('config') ? $container->get('config') : [];
        $setting = isset($config['session']) ? $config['session'] : [];

        return new SessionMiddleware($store, $setting);
    }
}
