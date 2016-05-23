<?php

namespace Pawon\Contrib\Bus;

use League\Tactician\CommandBus;
use Illuminate\Contracts\Queue\Queue;
use League\Tactician\Handler\Locator\CallableLocator;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Plugins\LockingMiddleware;
use Interop\Container\ContainerInterface as Container;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\MethodNameInflector\InvokeInflector;

class CommandBusFactory
{
    /**
     *
     */
    public function __invoke(Container $container)
    {
        $locator = new CallableLocator(function ($commandName) use ($container) {
            $commandName = str_replace('Jobs', 'Workers', $commandName);
            return $container->get($commandName);
        });

        $handlerMiddleware = new CommandHandlerMiddleware(
            $extractor = new ClassNameExtractor(),
            $locator,
            $inflector = new InvokeInflector()
        );

        $queued = new QueueCommandHandler(
            $container->get(Queue::class),
            $extractor,
            $locator,
            $inflector
        );

        return new CommandBus([new LockingMiddleware(), $queued, $handlerMiddleware]);
    }
}
