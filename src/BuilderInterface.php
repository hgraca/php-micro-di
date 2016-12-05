<?php

namespace Hgraca\MicroDI;

use InvalidArgumentException;

interface BuilderInterface
{
    /**
     * Builds a new instance, resolving all its dependencies
     *
     * @return mixed
     */
    public function build(string $class, array $arguments = []);

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
