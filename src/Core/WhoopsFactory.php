<?php

namespace Pawon\Core;

use Interop\Container\ContainerInterface;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Run as Whoops;

class WhoopsFactory
{
    /**
     * Create and return an instance of the Whoops runner.
     *
     * @param ContainerInterface $container
     * @return Whoops
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = isset($config['whoops']) ? $config['whoops'] : [];

        $whoops = new Whoops();
        $whoops->writeToOutput(false);
        $whoops->allowQuit(false);
        $whoops->pushHandler($container->get('Pawon\Core\WhoopsPageHandler'));
        $this->registerJsonHandler($whoops, $config);
        $whoops->register();
        return $whoops;
    }

    /**
     * If configuration indicates a JsonResponseHandler, configure and register it.
     *
     * @param Whoops $whoops
     * @param array|\ArrayAccess $config
     */
    private function registerJsonHandler(Whoops $whoops, $config)
    {
        if (! isset($config['json_exceptions']['display'])
            || empty($config['json_exceptions']['display'])
        ) {
            return;
        }

        $handler = new JsonResponseHandler();

        if (isset($config['json_exceptions']['show_trace'])) {
            $handler->addTraceToOutput(true);
        }

        if (isset($config['json_exceptions']['ajax_only'])) {
            $handler->onlyForAjaxRequests(true);
        }

        $whoops->pushHandler($handler);
    }
}
