<?php

namespace Pawon\Queue;

use Illuminate\Support\Arr;
use Interop\Container\ContainerInterface;
use Illuminate\Database\ConnectionResolverInterface as DBConnection;
use Illuminate\Contracts\Queue\Factory as FactoryContract;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class QueueServiceFactory
{
    /**
     *
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        if ($requestedName === FactoryContract::class) {
            return $this->createQueueFactory($container);
        }

        if ($requestedName === QueueContract::class) {
            return $this->createQueue($container);
        }

        if ($requestedName === Failed\FailedJobProviderInterface::class) {
            return $this->createFailedJobProvider($container);
        }

        if ($requestedName === Processor\Listener::class) {
            return $this->createListener($container);
        }

        throw new \RuntimeException('invalid requested name');
    }

    /**
     *
     */
    protected function createQueueFactory($container)
    {
        $factory = new QueueFactory($container);

        $this->registerConnectors($factory, $container);

        return $factory;
    }

    /**
     *
     */
    protected function createQueue($container)
    {
        $factory = $container->get(FactoryContract::class);

        return $factory->connection();
    }

    /**
     *
     */
    protected function createFailedJobProvider($container)
    {
        $config = $container->get('config');
        $failed = Arr::get($config, 'queue.failed');

        if (isset($failed['table'])) {
            $db = $container->get(DBConnection::class);

            return new Failed\DatabaseFailedJobProvider(
                $db,
                $failed['database'],
                $failed['table']
            );
        } else {
            return new Failed\NullFailedJobProvider();
        }
    }

    /**
     *
     */
    protected function createListener($container)
    {
        $factory = $container->get(FactoryContract::class);
        $failed = $container->get(Failed\FailedJobProviderInterface::class);

        return new Processor\Listener($factory, $failed);
    }

    /** Register the connectors on the queue manager.
     * @param \Illuminate\Queue\QueueManager $manager
     */
    public function registerConnectors($factory, $container)
    {
        foreach (['Sync', 'Database', 'Beanstalkd'] as $connector) {
            $this->{"register{$connector}Connector"}($factory, $container);
        }
    }

    /**
     * Register the Sync queue connector.
     *
     * @param \Illuminate\Queue\QueueManager $manager
     */
    protected function registerSyncConnector($factory, $container)
    {
        $factory->addConnector('sync', function () {
            return new Connectors\SyncConnector();
        });
    }

    /**
     * Register the Beanstalkd queue connector.
     *
     * @param \Illuminate\Queue\QueueManager $manager
     */
    protected function registerBeanstalkdConnector($factory, $container)
    {
        $factory->addConnector('beanstalkd', function () {
            return new Connectors\BeanstalkdConnector();
        });
    }

    /**
     * Register the database queue connector.
     *
     * @param \Illuminate\Queue\QueueManager $manager
     */
    protected function registerDatabaseConnector($factory, $container)
    {
        $factory->addConnector('database', function () use ($container) {
            $db = $container->get(DBConnection::class);

            return new Connectors\DatabaseConnector($db);
        });
    }
}
