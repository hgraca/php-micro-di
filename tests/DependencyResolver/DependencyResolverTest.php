<?php

namespace Hgraca\MicroDI\Test\DependencyResolver;

use Hgraca\Cache\CacheInterface;
use Hgraca\Cache\Exception\CacheItemNotFoundException;
use Hgraca\MicroDI\DependencyResolver\DependencyResolver;
use Hgraca\MicroDI\Test\Stub\Foo;
use Mockery;
use PHPUnit_Framework_TestCase;

final class DependencyResolverTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     *
     * @small
     */
    public function shouldReturnFromCacheIfFound()
    {
        $dummyDependencies = ['AAA'];
        $cacheMock = Mockery::mock(CacheInterface::class);
        $cacheMock->shouldReceive('fetch')->once()->andReturn($dummyDependencies);
        $cacheMock->shouldNotHaveReceived('save');
        $dependencyResolver = new DependencyResolver($cacheMock);

        self::assertEquals($dummyDependencies, $dependencyResolver->resolveDependencies([Foo::class, 'test']));
    }

    /**
     * @test
     *
     * @small
     */
    public function shouldSaveInCacheIfNotFound()
    {
        $dependencies = [0 => ['name' => 'name']];
        $cacheMock = Mockery::mock(CacheInterface::class);
        $cacheMock->shouldReceive('fetch')->once()->andThrow(CacheItemNotFoundException::class);
        $cacheMock->shouldReceive('save');
        $dependencyResolver = new DependencyResolver($cacheMock);

        self::assertEquals($dependencies, $dependencyResolver->resolveDependencies([Foo::class, 'test']));
    }
}
