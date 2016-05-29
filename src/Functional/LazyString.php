<?php

namespace Pawon\Functional;

/**
 * Used to signals the csrf token that the token actually used by the template
 * so it can response accordingly, eg not patch vary header to response. It work
 * because look like Twig render the template context using echo or function
 * string context.
 */
class LazyString
{
    protected $func;

    protected $result;

    /**
     *
     */
    public function __construct($func)
    {
        $this->func = $func;
    }

    /**
     *
     */
    public function __toString()
    {
        if ($this->result) {
            return $this->result;
        }
        try {
            $func = $this->func;

            return $this->result = $func();
        } catch (\Exception $e) {
            return '';
        }
    }
}
