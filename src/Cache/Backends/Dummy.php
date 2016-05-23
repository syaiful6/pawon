<?php

namespace Pawon\Cache\Backends;

class Dummy extends BaseCache
{
    /**
     *
     */
    public function add($key, $value, $version = null, $timeout = '__default__')
    {
    }

    /**
     *
     */
    public function get($key, $version = null, $default = null)
    {
    }

    /**
     *
     */
    public function set($key, $value, $version = null, $timeout = '__default__')
    {
    }

    /**
     *
     */
    public function delete($key, $version = null)
    {
    }

    /**
     *
     */
    public function clear()
    {
    }
}
