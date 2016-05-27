<?php

namespace Pawon\Functional;

/**
 * A callable object that will act differently according to first parameter. That first
 * parameter should be an object. The behaviour depending the registered handler
 * when they are not found then the it fallback to a given callback when created
 * this object.
 */
class Singledispatch
{
    use MethodResoulutionOrder;

    /**
     * @var callable The fallback when fail to search first parameter
     */
    protected $func;

    /**
     * @param callable $func the fallback
     */
    public function __construct(callable $func)
    {
        $this->func = $func;
    }

    /**
     * register the callback for the given cls
     *
     * @param string $cls The fully qualified class name to handle
     * @param callable $func The callback handler
     */
    public function register($cls, $func = null)
    {
        if ($func === null) {
            return function (callable $f) use ($cls) {
                $this->register($cls, $f);
            };
        }
        if (! is_callable($func)) {
            throw new \InvalidArgumentException(sprintf(
                'If argument 2 passed then it should callable. %s given',
                gettype($func)
            ));
        }
        $this->registry[$cls] = $func;
        return $func;
    }

    /**
     *
     */
    public function __invoke(...$args)
    {
        if (!$args) {
            throw new \RuntimeException('Expect at least 1 argument');
        }
        $cls = $args[0];
        if (! is_object($cls)) {
            throw new \InvalidArgumentException('Argument 1 must be an object');
        }
        // maybe we need some caching here
        $impl = $this->getImplementation(get_class($cls));
        if ($impl === null) {
            // no implementation found, use the default one
            $impl = $this->func;
        }
        return $impl(...$args);
    }
}
