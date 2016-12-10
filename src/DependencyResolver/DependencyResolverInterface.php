<?php

namespace Hgraca\MicroDI\DependencyResolver;

interface DependencyResolverInterface
{
    public function resolveDependencies($callable): array;
}
