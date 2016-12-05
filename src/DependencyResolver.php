<?php
namespace Hgraca\MicroDI;

use Hgraca\Cache\CacheInterface;
use Hgraca\Cache\Exception\CacheItemNotFoundException;
use Hgraca\Helper\ClassHelper;

final class DependencyResolver implements DependencyResolverInterface
{
    /** @var CacheInterface */
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function resolveDependencies(string $dependentClass, string $dependentMethod): array
    {
        $dependenciesKey = $this->getDependenciesKey($dependentClass, $dependentMethod);

        try {
            $dependencies = $this->cache->fetch($dependenciesKey);
        } catch (CacheItemNotFoundException $e) {
            $dependencies = ClassHelper::getParameters($dependentClass, $dependentMethod);
            $this->cache->save($dependenciesKey, $dependencies);
        }

        return $dependencies;
    }

    private function getDependenciesKey(string $class, string $method = '__construct'): string
    {
        return sprintf('%s::%s', str_replace('\\', '_', $class), $method);
    }
}
