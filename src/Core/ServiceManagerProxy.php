<?php

namespace Pawon\Core;

use Closure;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;
use InvalidArgumentException;
use Interop\Container\ContainerInterface;

class ServiceManagerProxy implements ContainerInterface
{
    /**
     * @var Interop\Container\ContainerInterface
     */
    protected $proxy;

    /**
     * @var self
     */
    protected static $instance;

    /**
     *
     */
    public function __construct(ContainerInterface $proxy)
    {
        $this->proxy = clone $proxy;
        $this->setInstance($this);
    }

    /**
     *
     */
    public function get($id)
    {
        return $this->proxy->get($id);
    }

    /**
     *
     */
    public function has($id)
    {
        return $this->proxy->has($id);
    }

    /**
     * Wrap the given closure such that its dependencies will be injected when executed.
     *
     * @param \Closure $callback
     * @param array    $parameters
     *
     * @return \Closure
     */
    public function wrap(Closure $callback, array $parameters = [])
    {
        return function () use ($callback, $parameters) {
            return $this->call($callback, $parameters);
        };
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param callable|string $callback
     * @param array           $parameters
     * @param string|null     $defaultMethod
     *
     * @return mixed
     */
    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        if ($this->isCallableWithAtSign($callback) || $defaultMethod) {
            return $this->callClass($callback, $parameters, $defaultMethod);
        }

        $dependencies = $this->getMethodDependencies($callback, $parameters);

        return call_user_func_array($callback, $dependencies);
    }

    /**
     * Determine if the given string is in Class@method syntax.
     *
     * @param mixed $callback
     *
     * @return bool
     */
    protected function isCallableWithAtSign($callback)
    {
        if (!is_string($callback)) {
            return false;
        }

        return strpos($callback, '@') !== false;
    }

    /**
     * Get all dependencies for a given method.
     *
     * @param callable|string $callback
     * @param array           $parameters
     *
     * @return array
     */
    protected function getMethodDependencies($callback, array $parameters = [])
    {
        $dependencies = [];

        foreach ($this->getCallReflector($callback)->getParameters() as $parameter) {
            $this->addDependencyForCallParameter($parameter, $parameters, $dependencies);
        }

        return array_merge($dependencies, $parameters);
    }

    /**
     * Get the proper reflection instance for the given callback.
     *
     * @param callable|string $callback
     *
     * @return \ReflectionFunctionAbstract
     */
    protected function getCallReflector($callback)
    {
        if (is_string($callback) && strpos($callback, '::') !== false) {
            $callback = explode('::', $callback);
        }

        if (is_array($callback)) {
            return new ReflectionMethod($callback[0], $callback[1]);
        }

        return new ReflectionFunction($callback);
    }

    /**
     * Get the dependency for the given call parameter.
     *
     * @param \ReflectionParameter $parameter
     * @param array                $parameters
     * @param array                $dependencies
     *
     * @return mixed
     */
    protected function addDependencyForCallParameter(ReflectionParameter $parameter, array &$parameters, &$dependencies)
    {
        if (array_key_exists($parameter->name, $parameters)) {
            $dependencies[] = $parameters[$parameter->name];

            unset($parameters[$parameter->name]);
        } elseif ($parameter->getClass()) {
            $dependencies[] = $this->make($parameter->getClass()->name);
        } elseif ($parameter->isDefaultValueAvailable()) {
            $dependencies[] = $parameter->getDefaultValue();
        }
    }

    /**
     * Call a string reference to a class using Class@method syntax.
     *
     * @param string      $target
     * @param array       $parameters
     * @param string|null $defaultMethod
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function callClass($target, array $parameters = [], $defaultMethod = null)
    {
        $segments = explode('@', $target);

        // If the listener has an @ sign, we will assume it is being used to delimit
        // the class name from the handle method name. This allows for handlers
        // to run multiple handler methods in a single class for convenience.
        $method = count($segments) == 2 ? $segments[1] : $defaultMethod;

        if (is_null($method)) {
            throw new InvalidArgumentException('Method not provided.');
        }

        return $this->call([$this->get($segments[0]), $method], $parameters);
    }

    /**
     *
     */
    public static function setInstance(ContainerInterface $proxy)
    {
        static::$instance = $proxy;
    }

    /**
     *
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     *
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->proxy, $method], $parameters);
    }
}
