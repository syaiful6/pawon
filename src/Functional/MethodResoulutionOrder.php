<?php

namespace Pawon\Functional;

use ReflectionClass;

trait MethodResoulutionOrder
{

    protected $registry = [];

    protected $cache;

    /**
     *
     */
    protected function getIteratorInheritence($cls)
    {
        $cl = $cls;
        do {
            yield $cls;
        } while ($cls = get_parent_class($cls));
        // we consider interface is low
        foreach (class_implements($cl) as $interface) {
            yield $interface;
        }
    }

    /**
     *
     */
    protected function getImplementation($cls)
    {
        $match = null;
        foreach ($this->getIteratorInheritence($cls) as $name) {
            if (array_key_exists($name, $this->registry)) {
                $match = $name;
                break;
            }
        }
        if ($match === null) {
            return;
        }
        return $this->registry[$match];
    }
}
