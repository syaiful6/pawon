<?php

namespace Pawon\Database;

use Pawon\Core\Exceptions\ImproperlyConfigured;
use Interop\Container\ContainerInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;

class DatabaseMigrationRepositoryFactory
{
    /**
     *
     */
    public function __invoke(ContainerInterface $container)
    {
        if ($container->has(ConnectionResolverInterface::class)) {
            $resolver = $container->get(ConnectionResolverInterface::class);
            return new DatabaseMigrationRepository(
                $resolver,
                $this->getMigrationTable($container)
            );
        } else {
            throw new ImproperlyConfigured(sprintf(
                'cant create database repository.need %s registered to container',
                ConnectionResolverInterface::class
            ));
        }
    }

    /**
     *
     */
    protected function getMigrationTable($container)
    {
        $config = $container->get('config');
        $dbconfig = $config['database'];

        if (is_array($dbconfig)) {
            return $dbconfig['migrations'];
        } else {
            return 'migrations';
        }
    }
}
