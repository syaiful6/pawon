<?php

namespace Pawon\Functional\Control;

use Pawon\Functional\Curry;

trait ApplicativeTrait
{
    /**
     *
     */
    public function liftA2(callable $func, Applicative $b)
    {
        return $b->apply($this->map($func));
    }
}
