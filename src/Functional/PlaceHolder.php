<?php

namespace Pawon\Functional;

/**
 * Just a litle help to mark the parameter is placeholder.
 */
class PlaceHolder
{
    private static $instance;

    private function __construct()
    {
    }

    /**
     *
     */
    public function create()
    {
        if (static::$instance === null) {
            return static::$instance = new static();
        }

        return static::$instance;
    }
}
