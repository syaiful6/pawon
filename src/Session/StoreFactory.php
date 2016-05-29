<?php

namespace Pawon\Session;

use Interop\Container\ContainerInterface;
use Pawon\Session\Backends\SessionBackendInterface;
use Pawon\Session\Backends\File as FileBackend;

class StoreFactory
{
    /**
     *
     */
    public function __invoke(ContainerInterface $container)
    {
        $backend = $container->has(SessionBackendInterface::class)
            ? $container->get(SessionBackendInterface::class)
            : new FileBackend();

        $config = $container->has('config') ? $container->get('config') : [];
        $setting = isset($config['session']) ? $config['session'] : [];
        $name = isset($setting['cookie']) ? $setting['cookie'] : 'expressive-session';

        return new Store($name, $backend);
    }
}
