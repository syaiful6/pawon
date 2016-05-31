<?php

namespace Pawon\Functional\Control;

/**
 * The Functor class is used for types that can be mapped over.
 * fmap id  ==  id
 * fmap (f . g)  ==  fmap f . fmap g
 */
interface Functor
{
	/**
	 * Map a function over wrapped values. then return instance of itself
     * Map knows how to apply functions to values that are wrapped in a context.
     *
     * @param callable $fn
     * @return Functional\Control\Functor
	 */
	public function map(callable $fn);

    /**
     * That is't, because PHP not functional, then we should define how to get
     * the wrapped value, right?
     *
     * @return mixed
     */
    public function extract();
}
