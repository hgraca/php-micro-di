<?php

namespace Hgraca\MicroDI;

use Hgraca\MicroDI\Exception\CanNotInstantiateDependenciesException;

final class Executor
{
    /**
     * @var BuilderInterface
     */
    private $builder;

    public function __construct(BuilderInterface $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Executes a callable, building and injecting its dependencies
     *
     * @param callable $callable
     * @param array    $arguments
     *
     * @throws CanNotInstantiateDependenciesException
     *
     * @return mixed
     */
    public function execute(callable $callable, array $arguments = [])
    {
        return call_user_func_array($callable, $this->builder->buildDependencies($callable, $arguments));
    }
}
