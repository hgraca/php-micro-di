<?php

namespace Hgraca\MicroDI\DependencyResolver;

use Hgraca\Cache\CacheInterface;
use Hgraca\Cache\Exception\CacheItemNotFoundException;
use Hgraca\Cache\Null\NullCache;
use Hgraca\Helper\InstanceHelper;

final class DependencyResolver implements DependencyResolverInterface
{
    /** @var CacheInterface */
    private $cache;

    public function __construct(CacheInterface $cache = null)
    {
        $this->cache = $cache ?? new NullCache();
    }

    public function resolveDependencies($callable): array
    {
        $dependenciesKey = $this->getDependenciesKey($callable);

        try {
            $dependencies = $this->cache->fetch($dependenciesKey);
        } catch (CacheItemNotFoundException $e) {
            $dependencies = InstanceHelper::getParameters($callable);
            $this->cache->save($dependenciesKey, $dependencies);
        }

        return $dependencies;
    }

    private function getDependenciesKey($callable): string
    {
        return is_object($callable) ? spl_object_hash($callable) : md5(serialize($callable));
    }
}
