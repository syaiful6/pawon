<?php

namespace Pawon\Auth;

use Pawon\Session\Store;
use Interop\Container\ContainerInterface as Container;

class AuthServiceFactory
{
    /**
     *
     */
    public function __invoke(
        Container $container,
        $requestedName,
        array $options = null
    ) {
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
    protected function createAuthenticator(Container $container)
    {
        $backends = [];
        if ($container->has('config')) {
            $config = $container->get('config');
            $backends = isset($config['auth']) ? $config['auth']['backends'] : null;
        }

        if (empty($backends)) {
            $backends = [ModelBackend::class];
        }

        $backends = array_map(function ($backend) use ($container) {
            return $container->get($backend);
        }, $backends);

        $authenticator = new Authenticator($backends);
        if ($container->has(Store::class)) {
            $authenticator->setSession($container->get(Store::class));
        }

        return $authenticator;
    }

    /**
     *
     */
    protected function createAuthenticationMiddleware(Container $container)
    {
        if ($container->has(Authenticator::class)) {
            return new AuthenticationMiddleware($container->get(Authenticator::class));
        }

        throw new \RuntimeException('missing authenticator class on container');
    }

    /**
     *
     */
    protected function createModelBackend(Container $container)
    {
        if ($container->has('config')) {
            $config = $container->get('config');
            $model = isset($config['auth']) ? $config['auth']['model'] : User::class;
        } else {
            $model = User::class;
        }

        return new ModelBackend($model);
    }
}
