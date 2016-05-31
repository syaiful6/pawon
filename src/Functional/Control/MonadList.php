<?php

namespace Pawon\Functional\Control;

use Headbanger\ArrayList;
use Headbanger\ArrayList\Slice;
use IteratorAggregate;
use function Itertools\map;
use function Itertools\product;

class MonadList implements Monad, Monoid
{
	use FunctorTrait, MonoidTrait;

	/**
	 *
	 */
	private $wrapped;

	/**
	 *
	 */
	public function __construct($wrapped = null)
	{
		if ($wrapped === null) {
			$wrapped = [];
		}
		if (!is_array($value) && !$value instanceof \Traversable) {
			throw new \InvalidArgumentException('Must be traversable');
		}
        if (!$wrapped instanceof ArrayList) {
            $wrapped = new ArrayList($wrapped);
        }
        $this->wrapped = function () use ($wrapped) {
            return $wrapped;
        };
	}

    /**
     *
     */
    public function cons($element)
    {
        $wrapped = call_user_func($this->wrapped);
        $lst = clone $wrapped;
        $lst->insert(0, $element);
        return new static($lst);
    }

    /**
     *
     */
    public function head()
    {
        $wrapped = call_user_func($this->wrapped);
        return $wrapped[0];
    }

    /**
     *
     */
    public function tail()
    {
        $wrapped = call_user_func($this->wrapped);
        $tail = $wrapped[new Slice(1)]; // it's new list
        return new static($tail);
    }

    /**
     *
     */
    public static function unit($x)
    {
        return (new static())->cons($x);
    }

    /**
     *
     */
    public static function pure(callable $x)
    {
        return static::unit($x);
    }

    /**
     *
     */
    public function none()
    {
        return !call_user_func($this->wrapped);
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
        return new static(map($fn, $value));
    }

    /**
     *
     */
    public function apply($something)
    {
        $results = [];
        $product = product($this, $something);
        $mapper = function ($item) {
            list($fn, $x) = $item;
            if (!$fn instanceof Curry) {
                $fn = new Curry($fn);
            }
            return $fn($x);
        };
        return new static(map($mapper, $product));
    }

    /**
     *
     */
    public static function empty()
    {
        return new static();
    }

    /**
     *
     */
    public function append(Monoid $other)
    {
        if ($this->none()) {
            return $other;
        }

        return ($this->tail()->append($other))->cons($this->head());
    }

    /**
     *
     */
    public function bind(callable $func)
    {
        return static::concat($this->map($func));
    }

    /**
     *
     */
    public function getIterator()
    {
       $identity = function ($x) {
            return $x;
       }
       return map($identity, call_user_func($this->wrapped));
    }
}
