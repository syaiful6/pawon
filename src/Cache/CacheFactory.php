<?php

namespace Pawon\Cache;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Contracts\Encryption\Encrypter;
use Interop\Container\ContainerInterface as Container;
use Illuminate\Database\ConnectionResolverInterface as DBConnection;

class CacheFactory
{
    /**
     *
     */
    public function __invoke(Container $container, $requestedName)
    {
        if ($requestedName === RateLimiter::class) {
            return new RateLimiter($container->get(Backends\BaseCache::class));
        }
        if ($container->has('config')) {
            $config = $container->get('config');
            $cache = $config['cache'];

            $driver = $cache['default'];
            $method = 'create'.Str::studly($driver).'Backend';
            $driverConf = $cache['stores'][$driver];

            $params = array_filter($cache, function ($key) {
                return $key !== 'stores';
            }, ARRAY_FILTER_USE_KEY);

            if (method_exists($this, $method)) {
                return $this->$method($container, $driverConf, $params);
            } else {
                throw new \RuntimeException(sprintf(
                    'Could not create %s driver. Currently only dummy, db and memcached available',
                    $driver
                ));
            }
        }
        throw new \RuntimeException(sprintf(
            'Could not create %s service. You maybe missing a config',
            $requestedName
        ));
    }

    /**
     *
     */
    protected function createDummyBackend(Container $container, array $config, array $params)
    {
        return new Backends\Dummy($params);
    }

    /**
     *
     */
    protected function createDatabaseBackend(Container $container, array $config, array $params)
    {
        $connection = $container->get(DBConnection::class)
                                ->connection(Arr::get($config, 'connection'));
        $encrypter = $container->get(Encrypter::class);

        $params['table'] = $config['table'];

        return new Backends\Database($connection, $encrypter, $params);
    }

    /**
     *
     */
    protected function createMemcachedBackend(Container $container, array $config, array $params)
    {
        $memcached = $this->connectToMemcache($config['servers']);

        return new Backends\Memcached($memcached, $params);
    }

    /**
     *
     */
    protected function connectToMemcache($servers)
    {
        $memcached = new \Memcached();
        foreach ($servers as $server) {
            $memcached->addServer(
                $server['host'],
                $server['port'],
                $server['weight']
            );
        }

        $memcachedStatus = $memcached->getVersion();

        if (!is_array($memcachedStatus)) {
            throw new \RuntimeException('No Memcached servers added.');
        }

        if (in_array('255.255.255', $memcachedStatus) && count(array_unique($memcachedStatus)) === 1) {
            throw new \RuntimeException('Could not establish Memcached connection.');
        }

        return $memcached;
    }
}
