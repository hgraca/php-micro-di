<?php

namespace Hgraca\MicroDI\Test;

use Hgraca\Cache\Null\NullCache;
use Hgraca\Helper\InstanceHelper;
use Hgraca\MicroDI\Adapter\Pimple\PimpleAdapter;
use Hgraca\MicroDI\Builder;
use Hgraca\MicroDI\BuilderInterface;
use Hgraca\MicroDI\DependencyResolver\DependencyResolver;
use Hgraca\MicroDI\Port\ContainerInterface;
use Hgraca\MicroDI\Test\Stub\Bar;
use Hgraca\MicroDI\Test\Stub\BarCallable;
use Hgraca\MicroDI\Test\Stub\Dummy;
use Hgraca\MicroDI\Test\Stub\DummyFactory;
use Hgraca\MicroDI\Test\Stub\DummyUser;
use Hgraca\MicroDI\Test\Stub\Foo;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Pimple\Container;

final class BuilderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     *
     * @small
     *
     * @covers \Hgraca\MicroDI\Builder::getInstance
     */
    public function getInstance_ShouldReturnInstanceFromContainer()
    {
        $containerAdapter = $this->setUpContainerAdapter();
        $builder          = $this->setUpBuilder($containerAdapter);
        $containerAdapter->addInstance($foo = new Foo());

        self::assertSame($foo, $builder->getInstance(Foo::class));
    }

    /**
     * @test
     *
     * @small
     *
     * @covers \Hgraca\MicroDI\Builder::getInstance
     */
    public function getInstance_ShouldBuildInstanceAndAddToContainer()
    {
        $containerAdapter = $this->setUpContainerAdapter();
        $builder          = $this->setUpBuilder($containerAdapter);
        self::assertFalse($containerAdapter->hasInstance(Foo::class));

        self::assertInstanceOf(Foo::class, $foo = $builder->getInstance(Foo::class));
        self::assertTrue($containerAdapter->hasInstance(Foo::class));
        self::assertSame($foo, $builder->getInstance(Foo::class));
    }

    /**
     * @test
     *
     * @small
     *
     * @covers \Hgraca\MicroDI\Builder::buildInstance
     */
    public function buildInstance_ShouldAlwaysBuildNewInstanceAndNotPutInContainer()
    {
        $containerAdapter = $this->setUpContainerAdapter();
        $builder          = $this->setUpBuilder($containerAdapter);
        self::assertFalse($containerAdapter->hasInstance(Foo::class));

        self::assertInstanceOf(Foo::class, $foo = $builder->buildInstance(Foo::class));
        self::assertNotSame($foo, $builder->buildInstance(Foo::class));
        self::assertFalse($containerAdapter->hasInstance(Foo::class));
    }

    /**
     * @test
     *
     * @small
     */
    public function buildDependencies_ShouldWorkWithArrayCallable()
    {
        $containerAdapter = $this->setUpContainerAdapter();
        $builder          = $this->setUpBuilder($containerAdapter);
        $containerAdapter->addInstance($foo = new Foo());
        $containerAdapter->addArgument('someText', $someText = 'some argument text in container');
        self::assertFalse($containerAdapter->hasInstance(DummyUser::class));
        self::assertFalse($containerAdapter->hasInstance(Dummy::class));
        $givenArg = 'some argument';

        $dependencies = $builder->buildDependencies([Bar::class, '__construct'], ['givenArg' => $givenArg]);

        self::assertSame($foo, $dependencies['foo']);
        self::assertSame($someText, $dependencies['someText']);
        self::assertInstanceOf(DummyUser::class, $dummyUser = $dependencies['dummyUser']);
        self::assertInstanceOf(Dummy::class, InstanceHelper::getProtectedProperty($dummyUser, 'dummy'));
        self::assertSame($givenArg, $dependencies['givenArg']);
    }

    /**
     * @test
     *
     * @small
     */
    public function buildDependencies_ShouldWorkWithObjectCallable()
    {
        $containerAdapter = $this->setUpContainerAdapter();
        $builder          = $this->setUpBuilder($containerAdapter);
        $containerAdapter->addInstance($foo = new Foo());
        $containerAdapter->addArgument('someText', $someText = 'some argument text in container');
        self::assertFalse($containerAdapter->hasInstance(DummyUser::class));
        self::assertFalse($containerAdapter->hasInstance(Dummy::class));
        $givenArg = 'some argument';

        $dependencies = $builder->buildDependencies(new BarCallable(), ['givenArg' => $givenArg]);

        self::assertSame($foo, $dependencies['foo']);
        self::assertSame($someText, $dependencies['someText']);
        self::assertInstanceOf(DummyUser::class, $dummyUser = $dependencies['dummyUser']);
        self::assertInstanceOf(Dummy::class, InstanceHelper::getProtectedProperty($dummyUser, 'dummy'));
        self::assertSame($givenArg, $dependencies['givenArg']);
    }

    /**
     * @test
     *
     * @small
     *
     * @expectedException \Hgraca\MicroDI\Exception\CanNotInstantiateDependenciesException
     */
    public function buildDependencies_ShouldThrowExceptionIfCanNotGetDependency()
    {
        $containerAdapter = $this->setUpContainerAdapter();
        $builder          = $this->setUpBuilder($containerAdapter);

        $builder->buildDependencies([Bar::class]);
    }

    /**
     * @test
     *
     * @small
     *
     * @expectedException InvalidArgumentException
     */
    public function buildDependencies_ShouldThrowExceptionIfClosure()
    {
        $containerAdapter = $this->setUpContainerAdapter();
        $builder          = $this->setUpBuilder($containerAdapter);

        $double = function ($value) {
            return $value * 2;
        };

        $builder->buildDependencies($double);
    }

    /**
     * @test
     *
     * @small
     *
     * @expectedException InvalidArgumentException
     */
    public function buildDependencies_ShouldThrowExceptionIfNotArrayNorObject()
    {
        $containerAdapter = $this->setUpContainerAdapter();
        $builder          = $this->setUpBuilder($containerAdapter);

        $builder->buildDependencies('some dummy stuff');
    }

    /**
     * @test
     *
     * @small
     *
     * @expectedException InvalidArgumentException
     *
     * @covers \Hgraca\MicroDI\Builder::buildFromFactory
     */
    public function buildFromFactory_ShouldThrowExceptionIfNotFactory()
    {
        $containerAdapter = $this->setUpContainerAdapter();
        $builder          = $this->setUpBuilder($containerAdapter);

        $builder->buildFromFactory(Foo::class);
    }

    /**
     * @test
     *
     * @small
     *
     * @covers \Hgraca\MicroDI\Builder::buildFromFactory
     */
    public function buildFromFactory_WithoutContextInContainer()
    {
        $containerAdapter = $this->setUpContainerAdapter();
        $builder          = $this->setUpBuilder($containerAdapter);

        self::assertInstanceOf(Dummy::class, $builder->buildFromFactory(DummyFactory::class));
    }

    /**
     * @test
     *
     * @small
     *
     * @covers \Hgraca\MicroDI\Builder::buildFromFactory
     */
    public function buildFromFactory_WithContextInContainer()
    {
        $containerAdapter = $this->setUpContainerAdapter();
        $builder          = $this->setUpBuilder($containerAdapter);
        $containerAdapter->addFactoryContext(DummyFactory::class, ['class' => Foo::class]);

        self::assertInstanceOf(Foo::class, $builder->buildFromFactory(DummyFactory::class));
    }

    private function setUpBuilder(ContainerInterface $containerAdapter): BuilderInterface
    {
        return new Builder($containerAdapter, new DependencyResolver(new NullCache()));
    }

    private function setUpContainerAdapter(): ContainerInterface
    {
        return new PimpleAdapter(new Container());
    }
}
