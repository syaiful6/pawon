<?php

namespace Pawon\Functional\Control;

interface Applicative extends Functor
{
    /**
     * Apply (<*>) is a beefed up fmap. It takes a functor value that
     * has a function in it and another functor, and extracts that
     * function from the first functor and then maps it over the second
     * one.
     */
    public function apply(Applicative $something);

    /**
     * The Applicative functor constructor.
     * Use pure if you're dealing with values in an applicative context
     * (using them with <*>); otherwise, stick to the default class
     * constructor.
     */
    public static function pure(callable $x);
}
