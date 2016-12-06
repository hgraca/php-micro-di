<?php

namespace Hgraca\MicroDI\Port;

interface ContainerInterface
{
    public function hasInstance(string $class): bool;

    public function hasArgument(string $parameter): bool;

    public function hasFactoryContext(string $factoryClass): bool;

    /**
     * @return mixed
     */
    public function getInstance(string $class);

    /**
     * @return mixed
     */
    public function getArgument(string $parameter);

    /**
     * @return array
     */
    public function getFactoryContext(string $factoryClass): array;

    public function addInstance($instance);
}
