<?php

namespace Pawon\Functional\Control;

interface Monoid
{
    /**
     *
     */
    public function append(Monoid $other);

    /**
     * mempty :: m
     */
    public static function mempty();

    /**
     *
     */
    public static function concat($xs);
}
