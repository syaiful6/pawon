<?php

namespace Pawon\Functional\Control;

use Pawon\Functional\Curry;

class Just extends Maybe
{
    /**
    * @wrapped value \Closure
    */
    private $wrapped;

    /**
     *
     */
    public function __construct($value)
    {
        $this->wrapped = function () use ($value) {
            return $value;
        };
    }

    /**
     *
     */
    public function map(callable $fn)
    {
        $value = call_user_func($this->wrapped);
        if (!$fn instanceof Curry) {
            $fn = new Curry($fn);
        }
        $result = $fn($value);
        return new static($result);
    }

    /**
     *
     */
    public function apply(Applicative $something)
    {
        return $something->map(call_user_func($this->wrapped));
    }

    /**
     *
     */
    public function append(Monoid $other)
    {
        if ($other instanceof Nothing) {
            return $this;
        }

        $otherValue = $other->extract();
        $value = call_user_func($this->wrapped);
        if (! $otherValue instanceof Monoid) {
            return new static($value + $otherValue);
        }

        return new static($value->append($other));
    }

    /**
     *
     */
    public function bind(callable $fn)
    {
        $value = call_user_func($this->wrapped);

        return $fn($value);
    }
}
