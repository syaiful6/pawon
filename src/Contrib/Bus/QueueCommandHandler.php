<?php

namespace Pawon\Contrib\Bus;

use League\Tactician\Middleware;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Queue\ShouldQueue;
use League\Tactician\Handler\Locator\HandlerLocator;
use League\Tactician\Exception\CanNotInvokeHandlerException;
use League\Tactician\Handler\CommandNameExtractor\CommandNameExtractor;
use League\Tactician\Handler\MethodNameInflector\MethodNameInflector;

class QueueCommandHandler implements Middleware
{
    protected $queue;

    /**
     * @var CommandNameExtractor
     */
    protected $commandNameExtractor;

    /**
     * @var HandlerLocator
     */
    protected $handlerLocator;

    /**
     * @var MethodNameInflector
     */
    protected $methodNameInflector;

    /**
     *
     */
    public function __construct(
        Queue $queue,
        CommandNameExtractor $commandNameExtractor,
        HandlerLocator $handlerLocator,
        MethodNameInflector $methodNameInflector
    ) {
        $this->queue = $queue;
        $this->commandNameExtractor = $commandNameExtractor;
        $this->handlerLocator = $handlerLocator;
        $this->methodNameInflector = $methodNameInflector;
    }

    /**
     * Executes a command and optionally returns a value.
     *
     * @param object   $command
     * @param callable $next
     *
     * @return mixed
     *
     * @throws CanNotInvokeHandlerException
     */
    public function execute($command, callable $next)
    {
        if (!$this->commandShouldBeQueued($command)) {
            return $next($command);
        }

        $handler = function ($job) use ($command) {
            $this->executeCommand($command);

            if (!$job->isDeletedOrReleased()) {
                $job->delete();
            }
        };

        $queue = $this->queue;

        if (isset($command->queue, $command->delay)) {
            return $queue->laterOn($command->queue, $command->delay, $handler);
        }

        if (isset($command->queue)) {
            return $queue->pushOn($command->queue, $handler);
        }

        if (isset($command->delay)) {
            return $queue->later($command->delay, $handler);
        }

        return $queue->push($handler);
    }

    /**
     *
     */
    protected function executeCommand($command)
    {
        $commandName = $this->commandNameExtractor->extract($command);
        $handler = $this->handlerLocator->getHandlerForCommand($commandName);
        $methodName = $this->methodNameInflector->inflect($command, $handler);

        // is_callable is used here instead of method_exists, as method_exists
        // will fail when given a handler that relies on __call.
        if (!is_callable([$handler, $methodName])) {
            throw CanNotInvokeHandlerException::forCommand(
                $command,
                "Method '{$methodName}' does not exist on handler"
            );
        }

        return $handler->{$methodName}($command);
    }

    /**
     * Determine if the given command should be queued.
     *
     * @param mixed $command
     *
     * @return bool
     */
    protected function commandShouldBeQueued($command)
    {
        return $command instanceof ShouldQueue;
    }
}
