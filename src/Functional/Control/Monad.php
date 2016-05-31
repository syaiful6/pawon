<?php

namespace Pawon\Functional\Control;

interface Monad extends Applicative
{
    /**
     * Monad bind method. This is the mother of all methods.
     */
    public function bind(callable $func);

    /**
     * Wrap a value in a default context.
     */
    public static function unit($value);
}
