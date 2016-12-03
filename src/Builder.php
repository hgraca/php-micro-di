<?php
namespace Hgraca\MicroDi;

use ArrayAccess as Container;
use Hgraca\Cache\PhpFile\PhpFileCache;
use Hgraca\Helper\ClassHelper;
use Hgraca\MicroDi\Factory\Contract\FactoryInterface;
use InvalidArgumentException;

class Builder
{
    /**
     * @var Container
     */
    protected $container;

    /** @var string[] */
    protected $dependentStack = [];

    /** @var  string[] */
    protected $argumentNameStack = [];

    /** @var PhpFileCache */
    protected $dependencyMapper;

    public function __construct(Container $container, PhpFileCache $dependencyMapper)
    {
        $this->container        = $container;
        $this->dependencyMapper = $dependencyMapper;
    }

    /**
     * Returns an instance of the required class from the container.
     * If it does not find one, it will create it and put it in the container before returning it
     *
     * @return mixed
     */
    public function build(string $class, array $arguments = [])
    {
        if (! isset($this->container[$class])) {
            return $this->buildNewAndCache($class, $arguments);
        }

        return $this->container[$class];
    }

    /**
     * Builds an instance, and put it in the container before returning it
     *
     * @return mixed
     */
    protected function buildNewAndCache(string $class, array $arguments = [])
    {
        $this->container[$class] = function () use ($class, $arguments) {
            return $this->buildNew($class, $arguments);
        };

        return $this->container[$class];
    }

    /**
     * Builds an instance, resolving all its dependencies
     *
     * @return mixed
     */
    public function buildNew(string $class, array $arguments = [])
    {
        $dependencies = $this->getDependencies([$class, '__construct'], $arguments);

        $metaClass = new \ReflectionClass($class);

        return $metaClass->newInstanceArgs($dependencies);
    }

    /**
     * Instantiates the factory and calls the `create` method on it.
     * The given arguments will be used both when instantiating the factory and when calling the `create` method.
     * They will be injected if the arguments keys match any of the dependencies names.
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    public function buildFromFactory(string $factoryClass, array $arguments = [])
    {
        if (! is_a($factoryClass, FactoryInterface::class, true)) {
            throw new InvalidArgumentException(
                "The given factory class $factoryClass must implement " . FactoryInterface::class
            );
        }

        /** @var FactoryInterface $factory */
        $factory = $this->build($factoryClass, $arguments);

        $context = $this->container->offsetExists("$factoryClass.context")
            ? $this->container["$factoryClass.context"]
            : [];

        return $factory->create($context);
    }

    /**
     * Executes a callable, building and injecting its dependencies
     *
     * @param string[]|callable $callable
     * @param array             $arguments
     *
     * @return mixed
     */
    public function call($callable, array $arguments = [])
    {
        return call_user_func_array($callable, $this->getDependencies($callable, $arguments));
    }

    /**
     * @return mixed[]
     */
    public function getDependencies(array $callable, array $arguments = [])
    {
        $dependentClass  = is_string($callable[0]) ? $callable[0] : get_class($callable[0]);
        $dependentMethod = $callable[1];

        $dependencies = $this->getDependenciesFromCache($dependentClass, $dependentMethod, $arguments);

        if (false === $dependencies) {
            $dependencies = $this->getDependenciesFromReflection($dependentClass, $dependentMethod, $arguments);
        }

        return $dependencies;
    }

    public function getDependentClass(): string
    {
        return end($this->dependentStack);
    }

    public function getArgumentName(): string
    {
        return end($this->argumentNameStack);
    }

    /**
     * @return string[]
     */
    public function getDependentStack(): array
    {
        return $this->dependentStack;
    }

    protected function getDependenciesKey(string $class, string $method = '__construct'): string
    {
        return sprintf('%s::%s', str_replace('\\', '_', $class), $method);
    }

    /**
     * @return array|bool
     */
    protected function getDependenciesFromCache(string $dependentClass, string $dependentMethod, array $arguments = [])
    {
        $key = $this->getDependenciesKey($dependentClass, $dependentMethod);

        if (! $this->dependencyMapper->contains($key)) {
            return false;
        }

        return $this->extractDependencies($dependentClass, $this->dependencyMapper->fetch($key), $arguments);
    }

    protected function getDependenciesFromReflection(
        string $dependentClass,
        string $dependentMethod,
        array $arguments = []
    ): array {
        $reflectionParameters = ClassHelper::getParameters($dependentClass, $dependentMethod);

        $dependenciesKey = $this->getDependenciesKey($dependentClass, $dependentMethod);
        $this->dependencyMapper->save($dependenciesKey, $reflectionParameters);

        return $this->extractDependencies($dependentClass, $reflectionParameters, $arguments);
    }

    protected function extractDependencies(string $dependentClass, array $parameters, array $arguments): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $parameterName = $parameter['name'];
            if ($this->dependencyIsInArguments($parameterName, $arguments)) {
                $this->setDependencyFromArguments($dependencies, $parameterName, $arguments);
            } elseif ($this->dependencyIsObjectOrInterface($parameter)) {
                $this->buildAndSetDependency($dependencies, $dependentClass, $parameterName, $parameter);
            } elseif ($this->dependencyIsInContainer($parameterName)) {
                $this->setDependencyFromContainer($parameterName, $dependencies);
            }
        }

        return $dependencies;
    }

    protected function pushDependentClass(string $dependentClass)
    {
        $this->dependentStack[] = $dependentClass;
    }

    protected function popDependentClass(): string
    {
        return array_pop($this->dependentStack);
    }

    protected function pushArgumentName(string $argumentName)
    {
        $this->argumentNameStack[] = $argumentName;
    }

    protected function popArgumentName(): string
    {
        return array_pop($this->argumentNameStack);
    }

    /**
     * TODO figure out the return type
     */
    protected function setDependencyFromArguments(array &$dependencies, string $parameterName, array $arguments)
    {
        return $dependencies[$parameterName] = $arguments[$parameterName];
    }

    protected function dependencyIsInArguments(string $parameterName, array $arguments): bool
    {
        return array_key_exists($parameterName, $arguments);
    }

    protected function dependencyIsObjectOrInterface(array $parameter): bool
    {
        return isset($parameter['class']) && (class_exists($parameter['class']) || interface_exists($parameter['class']));
    }

    /**
     * TODO figure out the type of the `$parameter` argument
     */
    protected function buildAndSetDependency(array &$dependencies, string $dependentClass, string $parameterName, $parameter)
    {
        $this->pushDependentClass($dependentClass);
        $this->pushArgumentName($parameterName);
        $dependencies[$parameterName] = $this->build($parameter['class'], []);
        $this->popDependentClass();
        $this->popArgumentName();
    }

    protected function dependencyIsInContainer(string $parameterName): bool
    {
        return isset($this->container[$parameterName]);
    }

    protected function setDependencyFromContainer(string $parameterName, array &$dependencies)
    {
        $dependencies[$parameterName] = $this->container[$parameterName];
    }
}
