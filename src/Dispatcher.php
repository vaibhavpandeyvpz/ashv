<?php

/*
 * This file is part of vaibhavpandeyvpz/ashv package.
 *
 * (c) Vaibhav Pandey <contact@vaibhavpandey.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.md.
 */

namespace Ashv;

/**
 * Class Dispatcher
 * @package Ashv
 */
class Dispatcher implements ContainerAwareInterface, DispatcherInterface
{
    use ContainerAwareTrait;

    /**
     * @var string|null
     */
    protected $actionSuffix = 'Action';

    /**
     * @var string|null
     */
    protected $controllerSuffix = 'Controller';

    /**
     * @var string|null
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $separator;

    /**
     * Dispatcher constructor.
     * @param string $separator
     */
    public function __construct($separator = '#')
    {
        $this->separator = $separator;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($callback, array $params)
    {
        if (is_callable($callback)) {
            return $this->invoke($callback, $params);
        }
        if (is_string($callback)) {
            if (false !== strpos($callback, $this->separator)) {
                list($controller, $action) = explode($this->separator, $callback, 2);
            } else {
                $controller = $callback;
                $action = '__invoke';
            }
        } elseif (is_array($callback) && (2 === count($callback))) {
            list($controller, $action) = $callback;
        } else {
            throw new \InvalidArgumentException(sprintf(
                "Failed to resolve '%s' to a valid callable",
                is_object($callback) ? get_class($callback) : gettype($callback)
            ));
        }
        if ($this->container->has($controller)) {
            $controller = $this->container->get($controller);
        } else {
            if ($this->namespace && ('\\' !== $controller[0]) && (0 !== strpos($controller, $this->namespace))) {
                $controller = $this->namespace . '\\' . $controller;
            }
            if ($this->controllerSuffix) {
                $controller .= $this->controllerSuffix;
            }
            $controller = $this->make($controller, $params);
            if ($controller instanceof ContainerAwareInterface) {
                $controller = $controller->withContainer($this->container);
            }
        }
        if ($this->actionSuffix && ('__invoke' !== $action)) {
            $action .= $this->actionSuffix;
        }
        return $this->invoke([$controller, $action], $params);
    }


    /**
     * @param callable $callable
     * @param array $params
     * @return mixed
     */
    protected function invoke($callable, array $params)
    {
        if (is_object($callable) && !($callable instanceof \Closure)) {
            $callable = [$callable, '__invoke'];
        } elseif (is_string($callable) && preg_match('~^(?P<class>[^:]+)::(?P<method>.+)$~', $callable, $matches)) {
            $callable = [$matches['class'], $matches['method']];
        }
        if (is_array($callable)) {
            $reflection = new \ReflectionMethod($callable[0], $callable[1]);
            $args = $this->resolve($reflection, $params);
            return $reflection->invokeArgs($reflection->isStatic() ? null : $callable[0], $args);
        } else {
            $reflection = new \ReflectionFunction($callable);
            $args = $this->resolve($reflection, $params);
            return $reflection->invokeArgs($args);
        }
    }

    /**
     * @param string $class
     * @param array $params
     * @return object
     */
    protected function make($class, array $params)
    {
        $clazz = new \ReflectionClass($class);
        if ($constructor = $clazz->getConstructor()) {
            $args = $this->resolve($constructor, $params);
            return $clazz->newInstanceArgs($args);
        } else {
            return $clazz->newInstance();
        }
    }

    /**
     * @param \ReflectionFunctionAbstract $function
     * @param array $params
     * @return array
     */
    protected function resolve(\ReflectionFunctionAbstract $function, array $params)
    {
        $args = [];
        foreach ($function->getParameters() as $parameter) {
            if (isset($params[$param = $parameter->getName()])) {
                $args[] = $params[$param];
            } elseif ($this->container->has($param)) {
                $args[] = $this->container->get($param);
            } elseif ($class = $parameter->getClass()) {
                $class = $class->getName();
                if ($this->container->has($class)) {
                    $args[] = $this->container->get($class);
                } elseif (($class === 'Psr\\Container\\ContainerInterface') || in_array('Psr\\Container\\ContainerInterface', class_implements($class))) {
                    $args[] = $this->container;
                } else {
                    $args[] = $this->make($class, $params);
                }
            } elseif ($parameter->isOptional()) {
                break;
            } else {
                throw new \RuntimeException("Unable to resolve '{$param}' parameter for ??::'{$function->getName()}'.");
            }
        }
        return $args;
    }

    /**
     * @param string|null $suffix
     * @return $this
     */
    public function setActionSuffix($suffix)
    {
        $this->actionSuffix = $suffix;
        return $this;
    }

    /**
     * @param string|null $suffix
     * @return $this
     */
    public function setControllerSuffix($suffix)
    {
        $this->controllerSuffix = $suffix;
        return $this;
    }

    /**
     * @param string|null $namespace
     * @return $this
     */
    public function setDefaultNamespace($namespace)
    {
        $this->namespace = rtrim($namespace, '\\');
        return $this;
    }
}
