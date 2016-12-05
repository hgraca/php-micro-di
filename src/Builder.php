<?php
namespace Hgraca\MicroDI;

use Hgraca\Helper\InstanceHelper;
use Hgraca\MicroDI\Exception\CanNotInstantiateDependenciesException;
use Hgraca\MicroDI\Port\ContainerInterface;
use InvalidArgumentException;

final class Builder implements BuilderInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var string[] */
    private $dependentStack = [];

    /** @var  string[] */
    private $argumentNameStack = [];

    /** @var DependencyResolverInterface */
    private $dependencyResolver;

    public function __construct(ContainerInterface $container, DependencyResolverInterface $dependencyResolver)
    {
        $this->container          = $container;
        $this->dependencyResolver = $dependencyResolver;
    }

    /**
     * @throws CanNotInstantiateDependenciesException
     */
    public function build(string $class, array $arguments = [])
    {
        if (! $this->container->hasInstance($class)) {
            $dependencies = $this->buildDependencies([$class, '__construct'], $arguments);

            $this->container->addInstance(InstanceHelper::createInstance($class, $dependencies));
        }

        return $this->container->getInstance($class);
    }

    public function buildFromFactory(string $factoryClass, array $arguments = [])
    {
        if (! is_a($factoryClass, FactoryInterface::class, true)) {
            throw new InvalidArgumentException(
                "The given factory class $factoryClass must implement " . FactoryInterface::class
            );
        }

        /** @var FactoryInterface $factory */
        $factory = $this->build($factoryClass, $arguments);

        $context = $this->container->hasFactoryContext($factoryClass)
            ? $this->container->getFactoryContext($factoryClass)
            : [];

        return $factory->create($context);
    }

    /**
     * @throws CanNotInstantiateDependenciesException
     */
    public function buildDependencies(array $callable, array $arguments = []): array
    {
        $dependentClass  = is_string($callable[0]) ? $callable[0] : get_class($callable[0]);
        $dependentMethod = $callable[1];

        $dependencies = $this->dependencyResolver->resolveDependencies($dependentClass, $dependentMethod);

        return $this->instantiateDependencies($dependentClass, $dependencies, $arguments);
    }

    /**
     * @throws CanNotInstantiateDependenciesException
     */
    private function instantiateDependencies(string $dependentClass, array $parameters, array $arguments): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependencies = $this->setParameterArgument($dependentClass, $arguments, $parameter);
        }

        return $dependencies;
    }

    /**
     * @throws CanNotInstantiateDependenciesException
     */
    private function setParameterArgument(string $dependentClass, array $arguments, array $parameter)
    {
        $parameterName = $parameter['name'];
        switch (true) {
            case $this->dependencyIsInGivenArguments($parameterName, $arguments):
                $this->setDependencyFromArguments($dependencies, $parameterName, $arguments);
                break;
            case $this->dependencyIsClassOrInterface($parameter):
                $this->setDependencyRecursively($dependencies, $dependentClass, $parameterName, $parameter);
                break;
            case $this->dependencyNameIsInContainer($parameterName):
                $this->setDependencyFromParameterInContainer($parameterName, $dependencies);
                break;
            default:
                throw new CanNotInstantiateDependenciesException(
                    "Could not instantiate dependency for class '$dependentClass', parameter '$parameterName'."
                );
        }

        return $dependencies;
    }

    private function dependencyIsInGivenArguments(string $parameterName, array $arguments): bool
    {
        return array_key_exists($parameterName, $arguments);
    }

    private function setDependencyFromArguments(array &$dependencies, string $parameterName, array $arguments)
    {
        return $dependencies[$parameterName] = $arguments[$parameterName];
    }

    private function dependencyIsClassOrInterface(array $parameter): bool
    {
        return isset($parameter['class']) &&
            (class_exists($parameter['class']) || interface_exists($parameter['class']));
    }

    private function setDependencyRecursively(
        array &$dependencies,
        string $dependentClass,
        string $parameterName,
        array $parameter
    ) {
        $this->pushDependentClass($dependentClass);
        $this->pushArgumentName($parameterName);
        $dependencies[$parameterName] = $this->build($parameter['class'], []);
        $this->popDependentClass();
        $this->popArgumentName();
    }

    private function dependencyNameIsInContainer(string $parameterName): bool
    {
        return $this->container->hasArgument($parameterName);
    }

    private function setDependencyFromParameterInContainer(string $parameterName, array &$dependencies)
    {
        $dependencies[$parameterName] = $this->container->getArgument($parameterName);
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
