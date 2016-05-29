<?php

namespace Pawon\Core;

use ReflectionClass;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Interop\Container\ContainerInterface as Container;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * An abstract factory that use ReflectionClass to inspect the class dependencies.
 *
 * Place this abstract factory on the last abstract factory chain. This is
 * last hope we can afford to resolve the requested class. It's capable to instantiate
 * class that have parameters type hint on their constructor and those classes/interface
 * available on the service locator, or the class is not have constructor
 * at all.
 */
class AbstractFactoryReflection implements AbstractFactoryInterface
{
    /**
     *
     */
    protected $buildStack = [];

    /**
     *
     */
    public function canCreate(Container $container, $requestedName)
    {
        // only support class
        return class_exists($requestedName);
    }

    /**
     * Note: Zend Service Manager catch all exception here and rethrow it.
     */
    public function __invoke(
        Container $container,
        $requestedName,
        array $options = null
    ) {
        $reflector = new ReflectionClass($requestedName);
        if (!$reflector->isInstantiable()) {
            if (!empty($this->buildStack)) {
                $previous = implode(', ', $this->buildStack);

                $message = "Target [$requestedName] is not instantiable while "
                    ."building [$previous].";
            } else {
                $message = "Target [$requestedName] is not instantiable.";
            }
            throw new ServiceNotCreatedException($message);
        }

        $this->buildStack[] = $requestedName;
        $constructor = $reflector->getConstructor();
        if ($constructor === null) {
            array_pop($this->buildStack);

            return new $requestedName();
        }
        $instances = $this->getDependencies(
            $container,
            $constructor->getParameters()
        );
        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param array $parameters
     * @param array $primitives
     *
     * @return array
     */
    protected function getDependencies($container, array $parameters)
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();

            if ($dependency === null) {
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                $dependencies[] = $this->resolveClass($container, $parameter);
            }
        }

        return $dependencies;
    }

    /**
     *
     */
    protected function resolveClass($container, $parameter)
    {
        try {
            return $container->get($parameter->getClass()->name);
        } catch (ContainerException $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }
            throw $e;
        }
    }

    /**
     *
     */
    protected function resolveNonClass($parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new ServiceNotCreatedException(
            "Unresolvable dependency resolving [$parameter]"
        );
    }
}
