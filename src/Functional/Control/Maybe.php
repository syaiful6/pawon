<?php

namespace Pawon\Functional\Control;

/**
 *
 */
abstract class Maybe implements Monad, Monoid
{
    use FunctorTrait, ApplicativeTrait, MonoidTrait;

    /**
     *
     */
    public static function pure(callable $x)
    {
        return new static($x);
    }

    /**
     *
     */
    public static function unit($x)
    {
        return new static($x);
    }

    /**
     *
     */
    public static function mempty()
    {
        return new Nothing();
    }
}
