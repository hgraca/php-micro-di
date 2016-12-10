<?php

namespace Hgraca\MicroDI;

use Closure;
use Hgraca\Helper\InstanceHelper;
use Hgraca\MicroDI\DependencyResolver\DependencyResolverInterface;
use Hgraca\MicroDI\Exception\CanNotInstantiateDependenciesException;
use Hgraca\MicroDI\Port\ContainerInterface;
use InvalidArgumentException;

final class Builder implements BuilderInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var string[] */
    private $dependentStack = [];

    /** @var string[] */
    private $argumentNameStack = [];

    /** @var DependencyResolverInterface */
    private $dependencyResolver;

    public function __construct(ContainerInterface $container, DependencyResolverInterface $dependencyResolver)
    {
        $this->container = $container;
        $this->dependencyResolver = $dependencyResolver;
    }

    public function getInstance(string $class, array $arguments = [])
    {
        if (!$this->container->hasInstance($class)) {
            $instance = $this->buildInstance($class, $arguments);
            $this->container->addInstance($instance);
        }

        return $this->container->getInstance($class);
    }

    public function buildInstance(string $class, array $arguments = [])
    {
        return InstanceHelper::createInstance(
            $class,
            $this->buildDependencies([$class, '__construct'], $arguments)
        );
    }

    public function buildFromFactory(string $factoryClass, array $arguments = [])
    {
        if (!is_a($factoryClass, FactoryInterface::class, $allowString = true)) {
            throw new InvalidArgumentException(
                "The given factory class $factoryClass must implement " . FactoryInterface::class
            );
        }

        /** @var FactoryInterface $factory */
        $factory = $this->getInstance($factoryClass, $arguments);

        $context = $this->container->hasFactoryContext($factoryClass)
            ? $this->container->getFactoryContext($factoryClass)
            : [];

        return $factory->create($context);
    }

    public function buildDependencies($callable, array $arguments = []): array
    {
        if ($callable instanceof Closure) {
            throw new InvalidArgumentException("Closures support is not implemented. The \$callable must be an array or an object with an '__invoke' method.");
        } elseif (is_array($callable)) {
            $dependentClass  = is_string($callable[0]) ? $callable[0] : get_class($callable[0]);
            $dependentMethod = $callable[1] ?? '__construct';
        } elseif (is_object($callable)) {
            $dependentClass  = get_class($callable);
            $dependentMethod = '__invoke';
        } else {
            throw new InvalidArgumentException("The \$callable must be an array or an object with an '__invoke' method.");
        }

        $dependencies = $this->dependencyResolver->resolveDependencies($dependentClass, $dependentMethod);

        return $this->prepareDependencies($dependentClass, $dependencies, $arguments);
    }

    private function prepareDependencies(string $dependentClass, array $parameters, array $arguments): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependencies[$parameter['name']] = $this->getParameterArgument($dependentClass, $parameter, $arguments);
        }

        return $dependencies;
    }

    private function getParameterArgument(string $dependentClass, array $parameter, array $arguments)
    {
        $parameterName = $parameter['name'];
        switch (true) {
            case $this->isDependencyInGivenArguments($parameterName, $arguments):
                return $this->getDependencyFromArguments($parameterName, $arguments);
            case $this->isDependencyClassOrInterface($parameter):
                return $this->getDependencyRecursively($dependentClass, $parameter);
            case $this->isDependencyNameInContainer($parameterName):
                return $this->getDependencyFromParameterInContainer($parameterName);
            default:
                throw new CanNotInstantiateDependenciesException(
                    "Could not get dependency for class '$dependentClass', parameter '$parameterName'."
                );
        }
    }

    private function isDependencyInGivenArguments(string $parameterName, array $arguments): bool
    {
        return array_key_exists($parameterName, $arguments);
    }

    private function getDependencyFromArguments(string $parameterName, array $arguments)
    {
        return $arguments[$parameterName];
    }

    private function isDependencyClassOrInterface(array $parameter): bool
    {
        return isset($parameter['class']) &&
            (class_exists($parameter['class']) || interface_exists($parameter['class']));
    }

    private function getDependencyRecursively(
        string $dependentClass,
        array $parameter
    ) {
        $this->pushDependentClass($dependentClass);
        $this->pushArgumentName($parameter['name']);
        $dependency = $this->getInstance($parameter['class']);
        $this->popDependentClass();
        $this->popArgumentName();

        return $dependency;
    }

    private function isDependencyNameInContainer(string $parameterName): bool
    {
        return $this->container->hasArgument($parameterName);
    }

    private function getDependencyFromParameterInContainer(string $parameterName)
    {
        return $this->container->getArgument($parameterName);
    }

    private function pushDependentClass(string $dependentClass)
    {
        $this->dependentStack[] = $dependentClass;
    }

    private function popDependentClass(): string
    {
        return array_pop($this->dependentStack);
    }

    private function pushArgumentName(string $argumentName)
    {
        $this->argumentNameStack[] = $argumentName;
    }

    private function popArgumentName(): string
    {
        return array_pop($this->argumentNameStack);
    }
}
