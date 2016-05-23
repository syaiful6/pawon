<?php

namespace Pawon\Database;

use Interop\Container\ContainerInterface;
use Pawon\Core\Exceptions\ImproperlyConfigured;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;

class MigratorFactory
{
    /**
     *
     */
    public function __invoke(ContainerInterface $container)
    {
        if ($container->has(ConnectionResolverInterface::class)) {
            $resolver = $container->get(ConnectionResolverInterface::class);

            if ($container->has(MigrationRepositoryInterface::class)) {
                $repository = $container->get(MigrationRepositoryInterface::class);

                return new Migrator($repository, $resolver);
            } else {
                throw new ImproperlyConfigured(sprintf(
                    'migrator need %s registered on container',
                    MigrationRepositoryInterface::class
                ));
            }
        } else {
            throw new ImproperlyConfigured(
                'You dont have any config!'
            );
        }
    }
}
