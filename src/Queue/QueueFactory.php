<?php

namespace Pawon\Queue;

use Closure;
use Illuminate\Support\Arr;
use Interop\Container\ContainerInterface as Container;
use Illuminate\Contracts\Queue\Factory as FactoryContract;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class QueueFactory implements FactoryContract
{
    /**
     * @var Interop\Container\ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $connections;

    /**
     * The array of resolved queue connectors.
     *
     * @var array
     */
    protected $connectors;

    /**
     *
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     *
     */
    public function connection($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->resolve($name);

            $this->connections[$name]->setContainer($this->container);

            $encripter = $this->container->get(EncrypterContract::class);
            $this->connections[$name]->setEncrypter($encripter);
        }

        return $this->connections[$name];
    }

    /**
     * Resolve a queue connection.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Queue\Queue
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        return $this->getConnector($config['driver'])->connect($config);
    }

    /**
     * Get the connector for a given driver.
     *
     * @param  string  $driver
     * @return \App\Queue\Connectors\Connector
     *
     * @throws \InvalidArgumentException
     */
    protected function getConnector($driver)
    {
        if (isset($this->connectors[$driver])) {
            return call_user_func($this->connectors[$driver]);
        }

        throw new \InvalidArgumentException("No connector for [$driver]");
    }

    /**
     * Add a queue connection resolver.
     *
     * @param  string    $driver
     * @param  \Closure  $resolver
     * @return void
     */
    public function addConnector($driver, Closure $resolver)
    {
        $this->connectors[$driver] = $resolver;
    }

    /**
     * Get the queue connection configuration.
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        if ($name === null || $name === 'null') {
            return ['driver' => 'null'];
        }
        $config = $this->container->get('config');

        return Arr::get($config, "queue.connections.{$name}");
    }

    /**
     *
     */
    protected function getDefaultDriver()
    {
        $config = $this->container->get('config');

        return Arr::get($config, 'queue.default');
    }
}
