<?php

namespace Pawon\Cache\Backends;

use OutOfBoundsException;

abstract class BaseCache
{
    use CacheKeyTrait;

    /**
     * @param int
     */
    protected $defaultTimeout;

    /**
     * @param int max
     */
    protected $maxEntries;

    /**
     *
     */
    protected $cullFrequency = 3;

    /**
     *
     */
    protected $keyPrefix;

    /**
     *
     */
    protected $version;

    /**
     *
     */
    protected $keyFunc;

    /**
     *
     */
    public function __construct(array $options = [])
    {
        $timeout = isset($options['timeout']) ? (int) $options['timeout'] : 300;
        $this->defaultTimeout = $timeout;

        $this->maxEntries = isset($options['max_entries'])
            ? (int) $options['max_entries']
            : 300;

        $this->cullFrequency = isset($options['cull_frequency'])
            ? (int) $options['cull_frequency']
            : 3;

        $this->keyPrefix = isset($options['key_prefix'])
            ? (string) $options['key_prefix']
            : '';

        $this->version = isset($options['version'])
            ? (string) $options['version']
            : 1;

        $this->keyFunc = isset($options['key_prefix'])
            ? $this->getKeyFunc($options['key_prefix'])
            : $this->getKeyFunc(null);
    }

    /**
     *
     */
    public function getBackendTimeout($timeout = '__default__')
    {
        if ($timeout === '__default__') {
            $timeout = $this->defaultTimeout;
        } elseif ($timeout === 0) {
            $timeout = -1;
        }

        return $timeout === null ? null : time() + $timeout;
    }

    /**
     *
     */
    public function makeKey($key, $version = null)
    {
        if ($version === null) {
            $version = $this->version;
        }

        $key = call_user_func($this->keyFunc, $key, $this->keyPrefix, $version);

        return $key;
    }

    /**
     *
     */
    public function getMany($keys, $version = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $val = $this->get($key, null, $version);
            if ($val !== null) {
                $result[$key] = $val;
            }
        }

        return $result;
    }

    /**
     *
     */
    public function getOrSet($key, $default, $version = null, $timeout = '__default__')
    {
        if ($default === null) {
            throw new \InvalidArgumentException('You need to specify a value.');
        }
        $val = $this->get($key, $version);
        if ($val === null) {
            if (is_callable($default)) {
                $default = $default();
            }
            $this->add($key, $default, $version, $timeout);
            // fect again to avoid race
            return $this->get($key, $version, $default);
        }

        return $val;
    }

    /**
     *
     */
    public function contains($key, $version = null)
    {
        return $this->get($key, $version) !== null;
    }

    /**
     *
     */
    public function increment($key, $delta = 1, $version = null)
    {
        $value = $this->get($key, $version);
        if ($value === null) {
            throw new OutOfBoundsException(sprintf(
                "Key '%s' not found",
                $key
            ));
        }
        $newValue = $value + $delta;
        $this->set($key, $value, $version);

        return $newValue;
    }

    /**
     *
     */
    public function decrement($key, $delta = 1, $version = null)
    {
        return $this->increment($key, -$delta, $version);
    }

    /**
     *
     */
    public function setMany($data, $version = null, $timeout = '__default__')
    {
        foreach ($data as $key => $val) {
            $this->set($key, $val, $version, $timeout);
        }
    }

    /**
     *
     */
    public function deleteMany($keys, $version = null)
    {
        foreach ($keys as $key) {
            $this->delete($key, $version);
        }
    }

    /**
     *
     */
    public function incrementVersion($key, $delta = 1, $version = null)
    {
        $version = $version === null ? $this->version : $version;
        $value = $this->get($key, $version);
        if ($value === null) {
            throw new OutOfBoundsException(sprintf(
                "Key '%s' not found",
                $key
            ));
        }
        $this->set($key, $value, $version + $delta);
        $this->delete($key, $version);

        return $version + $delta;
    }

    /**
     *
     */
    public function decrementVersion($key, $delta = 1, $version = null)
    {
        return $this->incrementVersion($key, -$delta, $version);
    }

    /**
     *
     */
    public function close()
    {
    }

    /**
     *
     */
    abstract public function add($key, $value, $version = null, $timeout = '__default__');

    /**
     *
     */
    abstract public function get($key, $version = null, $default = null);

    /**
     *
     */
    abstract public function set($key, $value, $version = null, $timeout = '__default__');

    /**
     *
     */
    abstract public function delete($key, $version = null);

    /**
     *
     */
    abstract public function clear();
}
