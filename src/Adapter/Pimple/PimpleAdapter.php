<?php

namespace Hgraca\MicroDI\Adapter\Pimple;

use Hgraca\MicroDI\Adapter\Exception\ArgumentNotFoundException;
use Hgraca\MicroDI\Adapter\Exception\ClassOrInterfaceNotFoundException;
use Hgraca\MicroDI\Adapter\Exception\FactoryContextNotFoundException;
use Hgraca\MicroDI\Adapter\Exception\InstanceNotFoundException;
use Hgraca\MicroDI\Adapter\Exception\NotAnObjectException;
use Hgraca\MicroDI\BuilderInterface;
use Hgraca\MicroDI\Port\ContainerInterface;
use Pimple\Container;

final class PimpleAdapter implements ContainerInterface
{
    const FACTORY_CONTEXT_POSTFIX = '.context';

    /** @var Container */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function addArgument(string $parameter, $argument)
    {
        $this->addKey($parameter, $argument);
    }

    public function hasArgument(string $parameter): bool
    {
        return $this->hasKey($parameter);
    }

    /**
     * @throws ArgumentNotFoundException
     *
     * @return mixed
     */
    public function getArgument(string $parameter)
    {
        if (! $this->hasArgument($parameter)) {
            throw new ArgumentNotFoundException();
        }

        return $this->getKey($parameter);
    }

    public function addFactoryContext(string $factoryClass, array $context)
    {
        return $this->addKey($this->generateFactoryContextKey($factoryClass), $context);
    }

    public function hasFactoryContext(string $factoryClass): bool
    {
        return $this->hasKey($this->generateFactoryContextKey($factoryClass));
    }

    public function getFactoryContext(string $factoryClass): array
    {
        if (! $this->hasFactoryContext($factoryClass)) {
            throw new FactoryContextNotFoundException();
        }

        return $this->getKey($this->generateFactoryContextKey($factoryClass));
    }

    public function addInstance($instance)
    {
        if (! is_object($instance)) {
            throw new NotAnObjectException();
        }

        $this->container[get_class($instance)] = $instance;
    }

    public function addInstanceLazyLoad(BuilderInterface $builder, string $class, array $arguments = [])
    {
        $this->assertClassOrInterfaceExists($class);

        $this->container[$class] = function () use ($builder, $class, $arguments) {
            return $builder->buildInstance($class, $arguments);
        };
    }

    /**
     * @throws ClassOrInterfaceNotFoundException
     */
    public function hasInstance(string $class): bool
    {
        $this->assertClassOrInterfaceExists($class);

        return $this->hasKey($class);
    }

    /**
     * @throws InstanceNotFoundException
     * @throws ClassOrInterfaceNotFoundException
     *
     * @return mixed
     */
    public function getInstance(string $class)
    {
        if (! $this->hasInstance($class)) {
            throw new InstanceNotFoundException();
        }

        return $this->getKey($class);
    }

    /**
     * @return mixed
     */
    private function addKey(string $key, $content)
    {
        return $this->container[$key] = $content;
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
        return $factoryClass . self::FACTORY_CONTEXT_POSTFIX;
    }

    /**
     * @throws ClassOrInterfaceNotFoundException
     */
    private function assertClassOrInterfaceExists(string $class)
    {
        if (! class_exists($class) && ! interface_exists($class)) {
            throw new ClassOrInterfaceNotFoundException();
        }
    }
}
