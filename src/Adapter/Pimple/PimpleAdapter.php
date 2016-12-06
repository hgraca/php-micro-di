<?php

namespace Hgraca\MicroDI\Adapter\Pimple;

use Hgraca\MicroDI\Adapter\Exception\NotAnObjectException;
use Hgraca\MicroDI\BuilderInterface;
use Hgraca\MicroDI\Port\ContainerInterface;
use Pimple\Container;

final class PimpleAdapter implements ContainerInterface
{
    /** @var Container */
    private $container;

    /** @var BuilderInterface */
    private $builder;

    public function __construct(Container $container, BuilderInterface $builder)
    {
        $this->container = $container;
        $this->builder = $builder;
    }

    public function hasInstance(string $class): bool
    {
        return $this->hasKey($class);
    }

    public function hasArgument(string $parameter): bool
    {
        return $this->hasKey($parameter);
    }

    public function hasFactoryContext(string $factoryClass): bool
    {
        return $this->hasKey($this->generateFactoryContextKey($factoryClass));
    }

    /**
     * @return mixed
     */
    public function getInstance(string $class)
    {
        return $this->getKey($class);
    }

    /**
     * @return mixed
     */
    public function getArgument(string $parameter)
    {
        return $this->getKey($parameter);
    }

    public function getFactoryContext(string $factoryClass): array
    {
        return $this->getKey($this->generateFactoryContextKey($factoryClass));
    }

    public function addInstance($instance)
    {
        if (!is_object($instance)) {
            throw new NotAnObjectException();
        }

        $this->container[get_class($instance)] = $instance;
    }

    public function addInstanceLazyLoad(string $class, array $arguments = [])
    {
        $this->container[$class] = function () use ($class, $arguments) {
            return $this->builder->build($class, $arguments);
        };
    }

    private function hasKey(string $key): bool
    {
        return isset($this->container[$key]);
    }

    /**
     * @return mixed
     */
    private function getKey(string $key)
    {
        return $this->container[$key];
    }

    private function generateFactoryContextKey(string $factoryClass): string
    {
        return $factoryClass . '.context';
    }
}
