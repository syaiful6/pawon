<?php

namespace Pawon\Console;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class Application extends SymfonyApplication
{
    /**
     *
     */
    protected $container;

    protected $lastOutput;

    /**
     *
     */
    public function __construct(
        ContainerInterface $container,
        $name = null,
        $version = null
    ) {
        $this->container = $container;
        $name = $name ?: 'Expressive';
        $version = $version ?: '1.0.0';
        parent::__construct($name, $version);

        $this->resolveCommandFromConfigs();
    }

    /**
     * Run an Artisan console command by name.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return int
     */
    public function call($command, array $parameters = [])
    {
        array_unshift($parameters, $command);

        $this->lastOutput = new BufferedOutput;

        $result = $this->run(new ArrayInput($parameter), $this->lastOutput);

        return $result;
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output()
    {
        return $this->lastOutput ? $this->lastOutput->fetch() : '';
    }

    /**
     * Add a command to the console.
     *
     * @param  \Symfony\Component\Console\Command\Command  $command
     * @return \Symfony\Component\Console\Command\Command
     */
    public function add(SymfonyCommand $command)
    {
        if ($command instanceof Command) {
            $command->setContainer($this->container);
        }

        return $this->addToParent($command);
    }

    /**
     *
     */
    protected function addToParent(SymfonyCommand $command)
    {
        parent::add($command);
    }

    /**
     * Add a command, resolving through the application.
     *
     * @param  string  $command
     * @return \Symfony\Component\Console\Command\Command
     */
    public function resolve($command)
    {
        return $this->add($this->container->get($command));
    }

    /**
     * Resolve an array of commands through the application.
     *
     * @param  array|mixed  $commands
     * @return $this
     */
    public function resolveCommands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        foreach ($commands as $command) {
            $this->resolve($command);
        }

        return $this;
    }

    /**
     *
     */
    protected function resolveCommandFromConfigs()
    {
        $configs = $this->container->get('config');
        if (is_array($configs['commands'])) {
            $this->resolveCommands($configs['commands']);
        }
    }
}
