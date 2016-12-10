<?php

namespace Hgraca\MicroDI;

use Hgraca\MicroDI\Exception\CanNotInstantiateDependenciesException;
use InvalidArgumentException;

interface BuilderInterface
{
    /**
     * Tries to get an instance from the container, if not builds a new instance, adding it to the container
     *
     * @return mixed
     */
    public function getInstance(string $class, array $arguments = []);

    /**
     * Builds a new instance, resolving all its dependencies
     *
     * @throws CanNotInstantiateDependenciesException
     *
     * @return mixed
     */
    public function buildInstance(string $class, array $arguments = []);

    /**
     * Instantiates the factory and calls the `create` method on it.
     * The given arguments will be used both when instantiating the factory and when calling the `create` method.
     * They will be injected if the arguments keys match any of the dependencies names.
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public function buildFromFactory(string $factoryClass, array $arguments = []);

    /**
     * @return mixed[]
     */
    public function buildDependencies(array $callable, array $arguments = []): array;
}
