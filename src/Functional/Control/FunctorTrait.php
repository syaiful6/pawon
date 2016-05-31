<?php

namespace Pawon\Functional\Control;

/**
 *
 */
trait FunctorTrait
{
    /**
     *
     */
    public function extract()
    {
        $v = null;
        $mapper = function ($g) use (&$v) {
            $v = $g;

            return $g;
        };

        $this->map($mapper);

        return $v;
    }
}
