<?php

namespace Pawon\Functional\Control;

use function Itertools\reduce;

trait MonoidTrait
{
    /**
     *
     */
    public static function concat($xs)
    {
        $reducer = function ($a, $b) {
            return $a->append($b);
        };

        return reduce($reducer, $xs, static::mempty());
    }
}
