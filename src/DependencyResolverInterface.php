<?php

namespace Hgraca\MicroDI;

interface DependencyResolverInterface
{
    public function resolveDependencies(string $dependentClass, string $dependentMethod): array;
}
