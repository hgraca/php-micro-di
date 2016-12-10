<?php

namespace Hgraca\MicroDI\Port;

use Hgraca\MicroDI\BuilderInterface;

interface ContainerInterface
{

    public function addArgument(string $parameter, $argument);

    public function hasArgument(string $parameter): bool;

    /**
     * @return mixed
     */
    public function getArgument(string $parameter);

    public function addFactoryContext(string $factoryClass, array $context);

    public function hasFactoryContext(string $factoryClass): bool;

    /**
     * @return array
     */
    public function getFactoryContext(string $factoryClass): array;

    public function addInstance($instance);

    public function addInstanceLazyLoad(BuilderInterface $builder, string $class, array $arguments = []);

    public function hasInstance(string $class): bool;

    /**
     * @return mixed
     */
    public function getInstance(string $class);
}
