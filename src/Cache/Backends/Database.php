<?php

namespace Pawon\Cache\Backends;

use Closure;
use Exception;
use Illuminate\Database\ConnectionInterface as Connection;
use Illuminate\Contracts\Encryption\Encrypter as Encrypter;

class Database extends BaseCache
{
    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * The encrypter instance.
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * The name of the cache table.
     *
     * @var string
     */
    protected $table;

    /**
     *
     */
    public function __construct(
        Connection $connection,
        Encrypter $encrypter,
        array $params = []
    ) {
        if (isset($params['table'])) {
            $table = $params['table'];
            unset($params['table']);
        } else {
            throw new \UnexpectedValueException('Need table key on argument 3');
        }
        parent::__construct($params);
        $this->encrypter = $encrypter;
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     *
     */
    public function get($key, $version = null, $default = null)
    {
        $key = $this->makeKey($key, $version);

        $cache = $this->table()->where('key', '=', $key)->first();

        if ($cache !== null) {
            if (is_array($cache)) {
                $cache = (object) $cache;
            }

            if (time() >= $cache->expiration) {
                $this->delete($key, $version);

                return $default;
            }

            return $this->encrypter->decrypt($cache->value);
        }

        return $default;
    }

    /**
     *
     */
    public function set($key, $value, $version = null, $timeout = '__default__')
    {
        $key = $this->makeKey($key, $version);
        $this->internalset('set', $key, $value, $version, $timeout);
    }

    /**
     *
     */
    public function add($key, $value, $version = null, $timeout = '__default__')
    {
        $key = $this->makeKey($key, $version);

        return $this->internalset('add', $key, $value, $version, $timeout);
    }

    /**
     *
     */
    public function delete($key, $version = null)
    {
        $key = $this->makeKey($key, $version);
        $this->table()->where('key', '=', $key)->delete();
    }

    /**
     * Remove all items from the cache.
     */
    public function clear()
    {
        $this->table()->delete();
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return int|bool
     */
    public function increment($key, $delta = 1, $version = null)
    {
        return $this->incrementOrDecrement(
            $key,
            $delta,
            $version,
            function ($current, $delta) {
                return $current + $delta;
            }
        );
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return int|bool
     */
    public function decrement($key, $delta = 1, $version = null)
    {
        return $this->incrementOrDecrement(
            $key,
            $delta,
            $version,
            function ($current, $delta) {
                return $current - $delta;
            }
        );
    }

    /**
     * Increment or decrement an item in the cache.
     *
     * @param string   $key
     * @param mixed    $value
     * @param \Closure $callback
     *
     * @return int|bool
     */
    protected function incrementOrDecrement($key, $delta, $version, Closure $callback)
    {
        return $this->connection->transaction(function () use ($key, $version, $delta, $callback) {
            $prefixed = $this->makeKey($key, $version);

            $cache = $this->table()->where('key', $prefixed)->lockForUpdate()->first();
            if ($cache === null) {
                return false;
            }

            if (is_array($cache)) {
                $cache = (object) $cache;
            }

            $current = $this->encrypter->decrypt($cache->value);
            $new = $callback($current, $delta);

            if (!is_numeric($current)) {
                return false;
            }

            $this->table()->where('key', $prefixed)->update([
                'value' => $this->encrypter->encrypt($new),
            ]);

            return $new;
        });
    }

    /**
     *
     */
    protected function internalset($mode, $key, $value, $version, $timeout)
    {
        $expiration = $this->getBackendTimeout($timeout);
        $value = $this->encrypter->encrypt($value);
        $count = $this->table()->count();
        $now = time();

        if ($count > $this->maxEntries) {
            $this->doCull($now);
        }
        try {
            $result = $this->table()->where('key', '=', $key)->first();
            if (is_array($result)) {
                $result = (object) $result;
            }

            if ($result && ($mode === 'set' || (
                $mode === 'add' && $result->expiration < $now))
            ) {
                $this->table()->where('key', '=', $key)->update(compact('value', 'expiration'));
            } else {
                $this->table()->insert(compact('key', 'value', 'expiration'));
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     *
     */
    protected function doCull($now)
    {
        if ($this->cullFrequency === 0) {
            $this->clear();
        } else {
            $this->table()->where('expiration', '<', $now)->delete();
            $num = $this->table()->count();
            if ($num > $this->maxEntries) {
                $cullNum = floor($num / $this->cullFrequency);
                $results = $this->table()->skip($cullNum)->delete();
            }
        }
    }

    /**
     * Get a query builder for the cache table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->connection->table($this->table);
    }

    /**
     * Get the underlying database connection.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get the encrypter instance.
     *
     * @return \Illuminate\Contracts\Encryption\Encrypter
     */
    public function getEncrypter()
    {
        return $this->encrypter;
    }
}
