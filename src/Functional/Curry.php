<?php

namespace Pawon\Functional;

use function Pawon\arity;

/**
 * Not much like haskell version, but it help a lot. We can do like this
 * $mapUpper = new Curry('array_map', 'strtoupper');
 * $upper = $mapUpper(['a', 'b', 'c']) -> ['A', 'B', 'C']
 * the $mapUpper can be called more than once, because it separated instance.
 * Unlike partial app, if the accumulated arguments so far not meet required
 * arguments of original function it will just return new Curry instance
 * (the implementation cloned current instance).
 */
class Curry
{
    /**
     * @var callable
     */
    protected $func;

    /**
     * @var accumulated arguments
     */
    protected $args;

    /**
     * @var integer
     */
    protected $arity;

    /**
     *
     */
    public function __construct(callable $func, ...$args)
    {
        $this->func = $func;
        $this->args = $args;
        $this->arity = arity($func) - count($args);
    }

    /**
     * Force the required arguments
     */
    public function withArity($num)
    {
        $me = clone $this;
        $me->arity = $num;
        return $me;
    }

    /**
     *
     */
    public function getArity()
    {
        return $this->arity;
    }

    /**
     *
     */
    public function __invoke(...$params)
    {
        $args = array_merge($this->args, $params);
        if (($this->arity - count($params)) <= 0) {
            return call_user_func_array($this->func, $args);
        }
        // clone so we dont mess it if we call more than once
        $me = clone $this;
        $me->args = $args;
        $me->arity -= count($params);

        return $me;
    }
}
