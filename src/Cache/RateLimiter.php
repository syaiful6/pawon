<?php

namespace Pawon\Cache;

use Pawon\Cache\Backends\BaseCache as Cache;

class RateLimiter
{
    /**
     * @var Pawon\Cache\Backends\BaseCache
     */
    protected $cache;

    /**
     * Create new RateLimiter.
     *
     * @param Pawon\Cache\Backends\BaseCache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     *
     */
    public function tooManyAttempts($key, $maxAttempts, $version = null, $timeout = 60)
    {
        if ($this->cache->contains($key.':lockout', $version)) {
            return true;
        }

        if ($this->attempts($key, $version) > $maxAttempts) {
            $this->cache->add($key.':lockout', time() + $timeout, $version, $timeout);

            return true;
        }

        return false;
    }

    /**
     *
     */
    public function hit($key, $version = null, $timeout = 60)
    {
        $this->cache->add($key, 1, $version, $timeout);

        return $this->cache->increment($key, 1, $version);
    }

    /**
     * Get the number of attempts for the given key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function attempts($key, $version = null)
    {
        return $this->cache->get($key, $version, 0);
    }

    /**
     * Get the number of retries left for the given key.
     *
     * @param string $key
     * @param int    $maxAttempts
     *
     * @return int
     */
    public function retriesLeft($key, $maxAttempts, $version = null)
    {
        $attempts = $this->attempts($key, $version);

        return $attempts === 0 ? $maxAttempts : $maxAttempts - $attempts + 1;
    }

    /**
     * Clear the hits and lockout for the given key.
     *
     * @param string $key
     */
    public function clear($key, $version = null)
    {
        $this->cache->delete($key, $version);

        $this->cache->delete($key.':lockout', $version);
    }

    /**
     * Get the number of seconds until the "key" is accessible again.
     *
     * @param string $key
     *
     * @return int
     */
    public function availableIn($key, $version = null)
    {
        return $this->cache->get($key.':lockout', $version) - time();
    }
}
