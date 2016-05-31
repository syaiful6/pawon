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
	public static function empty();

    /**
     *
     */
    public static function concat($xs);
}
