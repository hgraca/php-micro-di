<?php

namespace Hgraca\MicroDI\DependencyResolver;

interface DependencyResolverInterface
{
    public function resolveDependencies(string $dependentClass, string $dependentMethod): array;
}
