<?php

namespace Pawon\Cache\Backends;

use Memcached as CacheServer;

class Memcached extends BaseCache
{
    /**
     *
     */
    protected $memcached;

    /**
     *
     */
    public function __construct($memcached, array $params = [])
    {
        parent::__construct($params);
        $this->memcached = $memcached;
    }

    public function getBackendTimeout($timeout = '__default__')
    {
        if ($timeout === '__default__') {
            $timeout = $this->defaultTimeout;
        }

        if ($timeout === null) {
            return 0; // 0 mean never expired
        } elseif ((int) $timeout === 0) {
            $timeout = -1;
        }

        if ($timeout > 2592000) {
            $timeout += time();
        }

        return (int) $timeout;
    }

    public function add($key, $value, $version = null, $timeout = '__default__')
    {
        $key = $this->makeKey($key, $version);

        return $this->memcached->add($key, $value, $this->getBackendTimeout($timeout));
    }

    /**
     *
     */
    public function get($key, $version = null, $default = null)
    {
        $key = $this->makeKey($key, $version);
        $val = $this->memcached->get($key);
        if ($this->memcached->getResultCode() == 0) {
            return $val;
        }

        return $default;
    }

    /**
     *
     */
    public function set($key, $value, $version = null, $timeout = '__default__')
    {
        $key = $this->makeKey($key, $version);
        if (!$this->memcached->set($key, $value, $this->getBackendTimeout($timeout))) {
            // make sure the key doesn't keep its old value in case of failure
            // to set (memcached's 1MB limit)
            $this->memcached->delete($key);
        }
    }

    /**
     *
     */
    public function delete($key, $version = null)
    {
        $key = $this->makeKey($key, $version);
        $this->memcached->delete($key);
    }

    /**
     *
     */
    public function getMany($keys, $version = null)
    {
        $prefixedKeys = array_map(function ($key) use ($version) {
            return $this->makeKey($key, $version);
        }, $keys = is_array($keys) ? $keys : iterator_to_array($keys));

        $values = $this->memcached->getMulti(
            $prefixedKeys,
            null,
            CacheServer::GET_PRESERVE_ORDER
        );
        if ($this->memcached->getResultCode() != 0) {
            return array_fill_keys($keys, null);
        }

        return array_combine($keys, $values);
    }

    /**
     *
     */
    public function setMany($data, $version = null, $timeout = '__default__')
    {
        $prefixes = [];

        foreach ($values as $key => $value) {
            $k = $this->makeKey($key, $version);
            $prefixes[$k] = $value;
        }

        $this->memcached->setMulti($prefixes, $this->getBackendTimeout($timeout));
    }

    /**
     *
     */
    public function deleteMany($keys, $version = null)
    {
        $prefixedKeys = array_map(function ($key) use ($version) {
            return $this->makeKey($key, $version);
        }, is_array($keys) ? $keys : iterator_to_array($keys));

        $this->memcached->deleteMulti($prefixedKeys);
    }

    /**
     *
     */
    public function clear()
    {
        $this->memcached->flush();
    }
}
