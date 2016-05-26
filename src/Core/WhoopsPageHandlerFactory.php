<?php

namespace Pawon\Core;

use Interop\Container\ContainerInterface;
use Whoops\Handler\PrettyPageHandler;

class WhoopsPageHandlerFactory
{
    /**
     * @param ContainerInterface $container
     * @returns PrettyPageHandler
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = isset($config['whoops']) ? $config['whoops'] : [];

        $pageHandler = new PrettyPageHandler();

        $this->injectEditor($pageHandler, $config, $container);

        return $pageHandler;
    }

    /**
     *
     */
    private function injectEditor(PrettyPageHandler $handler, $config, ContainerInterface $container)
    {
        if (! isset($config['editor'])) {
            return;
        }

        $editor = $config['editor'];

        if (is_callable($editor)) {
            $handler->setEditor($editor);
            return;
        }

        if (! is_string($editor)) {
            throw new Exceptions\ImproperlyConfigured(sprintf(
                'Whoops editor must be a string editor name, string service name, or callable; received "%s"',
                (is_object($editor) ? get_class($editor) : gettype($editor))
            ));
        }

        if ($container->has($editor)) {
            $editor = $container->get($editor);
        }

        $handler->setEditor($editor);
    }
}
