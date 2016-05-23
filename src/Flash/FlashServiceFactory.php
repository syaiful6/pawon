<?php

namespace Pawon\Flash;

use Pawon\Session\Store;
use Interop\Container\ContainerInterface as Container;

class FlashServiceFactory
{
    /**
     *
     */
    public function __invoke(Container $container, $requestedName, array $opt = null)
    {
        if ($requestedName === Storage\BaseStorage::class) {
            return $this->createSessionStorage($container);
        }

        if ($requestedName === FlashMessageInterface::class) {
            return $this->createFlashMessage($container);
        }

        if ($requestedName === FlashMessageMiddleware::class) {
            return $this->createFlashMiddleware($container);
        }

        throw new \RuntimeException('Invalid service requested');
    }

    /**
     *
     */
    protected function createSessionStorage($container)
    {
        if ($container->has(Store::class)) {
            $session = $container->get(Store::class);

            return new Storage\Session($session);
        }

        throw new \RuntimeException(
            'cant create flash session storage without session store'
        );
    }

    /**
     *
     */
    protected function createFlashMessage($container)
    {
        $storage = $container->get(Storage\BaseStorage::class);

        return new FlashMessage($storage);
    }

    /**
     *
     */
    protected function createFlashMiddleware($container)
    {
        $flash = $container->get(FlashMessageInterface::class);
        $config = $container->get('config');

        return new FlashMessageMiddleware($flash, $config['debug']);
    }
}
