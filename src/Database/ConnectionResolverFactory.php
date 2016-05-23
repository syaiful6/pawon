<?php

namespace Pawon\Database;

use Illuminate\Support\Fluent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\ConnectionInterface;
use Interop\Container\ContainerInterface;
use Pawon\Foundation\Exceptions\ImproperlyConfigured;

class ConnectionResolverFactory extends Capsule
{
    protected $alreadySetup = false;

    /**
     *
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        if (!$this->alreadySetup) {
            $this->bootCapsule($container);
        }

        if ($requestedName === ConnectionResolverInterface::class) {
            return $this->getDatabaseManager();
        } elseif ($requestedName === ConnectionInterface::class) {
            $name = null;
            if ($options !== null && isset($options['connection'])) {
                $name = $options['connection'];
            }
            return $this->getDatabaseManager()->connection($name);
        } else {
            throw new ImproperlyConfigured(
                sprintf('can\'t resolve %s', $requestedName)
            );
        }
    }

    /**
     *
     */
    protected function bootCapsule($container)
    {
        $configs = $this->getDatabaseConfigs($container);
        $default = $configs['default'];
        if (!is_array($connections = $configs['connections'])) {
            throw new ImproperlyConfigured(
                'Your dont have any db connections on your config.'
            );
        }
        foreach ($connections as $name => $config) {
            $this->addConnection($config, $name);
            if ($default === $name) {
                $this->addConnection($config);
            }
        }
        if (isset($configs['fetch'])) {
            $this->setFetchMode($configs['fetch']);
        }
        $this->alreadySetup = true;
    }

    /**
     *
     */
    public function getDatabaseConfigs(ContainerInterface $container)
    {
        if ($container->has('config')) {
            $config = $container->get('config');
            $dbconfig = $config['database'];
            if (!is_array($dbconfig)) {
                throw new ImproperlyConfigured(
                    'Your db config must be an array'
                );
            }
            return $dbconfig;
        } else {
            throw new ImproperlyConfigured(
                'You dont have any config!'
            );
        }
    }
}
