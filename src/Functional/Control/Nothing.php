<?php

namespace Pawon\Functional\Control;

class Nothing extends Maybe
{
	/**
	 * map a nothing return nothing
	 */
	public function map(callable $fn)
    {
    	return clone $this;
    }

    /**
     * bind a nothing, return nothing. Nothing goes in, nothing goest out
     */
    public function bind(callable $func)
    {
        return clone $this;
    }

    /**
     * append nothing just return other
     */
    public function append(Monoid $other)
    {
        return $other;
    }

    /**
     * apply nothing return nothing
     */
    public function apply(Applicative $something)
    {
        return clone $this;
    }
}
