<?php

namespace Hgraca\MicroDI\Test\Adapter\Pimple;

use Hgraca\Helper\InstanceHelper;
use Hgraca\MicroDI\Adapter\Pimple\PimpleAdapter;
use Hgraca\MicroDI\Builder;
use Hgraca\MicroDI\BuilderInterface;
use Hgraca\MicroDI\DependencyResolver\DependencyResolver;
use Hgraca\MicroDI\Test\Stub\Foo;
use PHPUnit_Framework_TestCase;
use Pimple\Container;

final class PimpleAdapterTest extends PHPUnit_Framework_TestCase
{
    /** @var Container */
    private $container;

    /** @var PimpleAdapter */
    private $pimpleAdapter;

    /** @var BuilderInterface */
    private $builder;

    /**
     * @before
     */
    public function createPimpleContainer()
    {
        $this->container = new Container();

        $this->pimpleAdapter = new PimpleAdapter($this->container);

        $this->builder = new Builder($this->pimpleAdapter, new DependencyResolver());
    }

    /**
     * @test
     *
     * @small
     */
    public function addArgument()
    {
        $property = 'propertyName';
        $argument = ['a', 'b', 'c'];

        $this->pimpleAdapter->addArgument($property, $argument);

        self::assertSame($argument, InstanceHelper::getProtectedProperty($this->container, 'values')[$property]);
    }

    /**
     * @test
     *
     * @small
     */
    public function hasArgument_ReturnsTrue()
    {
        $property = 'propertyName';
        $argument = ['a', 'b', 'c'];

        $this->pimpleAdapter->addArgument($property, $argument);

        self::assertTrue($this->pimpleAdapter->hasArgument($property));
    }

    /**
     * @test
     *
     * @small
     */
    public function hasArgument_ReturnsFalse()
    {
        self::assertFalse($this->pimpleAdapter->hasArgument('propertyName'));
    }

    /**
     * @test
     *
     * @small
     */
    public function getArgument()
    {
        $property = 'propertyName';
        $argument = ['a', 'b', 'c'];

        $this->pimpleAdapter->addArgument($property, $argument);

        self::assertSame($argument, $this->pimpleAdapter->getArgument($property));
    }

    /**
     * @test
     *
     * @small
     *
     * @expectedException \Hgraca\MicroDI\Adapter\Exception\ArgumentNotFoundException
     */
    public function getArgument_ThrowsException()
    {
        $this->pimpleAdapter->getArgument('propertyName');
    }

    /**
     * @test
     *
     * @small
     */
    public function addFactoryContext()
    {
        $context = ['a', 'b', 'c'];

        $this->pimpleAdapter->addFactoryContext(Foo::class, $context);

        self::assertSame(
            $context,
            InstanceHelper::getProtectedProperty($this->container,
                'values')[Foo::class . PimpleAdapter::FACTORY_CONTEXT_POSTFIX]
        );
    }

    /**
     * @test
     *
     * @small
     */
    public function hasFactoryContext_ReturnsTrue()
    {
        $context = ['a', 'b', 'c'];

        $this->pimpleAdapter->addFactoryContext(Foo::class, $context);

        self::assertTrue($this->pimpleAdapter->hasFactoryContext(Foo::class));
    }

    /**
     * @test
     *
     * @small
     */
    public function hasFactoryContext_ReturnsFalse()
    {
        self::assertFalse($this->pimpleAdapter->hasFactoryContext(Foo::class));
    }

    /**
     * @test
     *
     * @small
     */
    public function getFactoryContext()
    {
        $context = ['a', 'b', 'c'];

        $this->pimpleAdapter->addFactoryContext(Foo::class, $context);

        self::assertSame($context, $this->pimpleAdapter->getFactoryContext(Foo::class));
    }

    /**
     * @test
     *
     * @small
     *
     * @expectedException \Hgraca\MicroDI\Adapter\Exception\FactoryContextNotFoundException
     */
    public function getFactoryContext_ThrowsExceptionIfNotFound()
    {
        $this->pimpleAdapter->getFactoryContext(Foo::class);
    }

    /**
     * @test
     *
     * @small
     *
     * @expectedException \Hgraca\MicroDI\Adapter\Exception\NotAnObjectException
     */
    public function addInstance_ThrowsExceptionIfNotAnObject()
    {
        $this->pimpleAdapter->addInstance('aaa');
    }

    /**
     * @test
     *
     * @small
     */
    public function addInstance_ShouldSetInstanceInContainer()
    {
        $this->pimpleAdapter->addInstance(new Foo);

        self::assertInstanceOf(
            Foo::class,
            InstanceHelper::getProtectedProperty($this->container, 'values')[Foo::class]
        );
    }

    /**
     * @test
     *
     * @small
     */
    public function addInstanceLazyLoad_ShouldSetCallableInContainer()
    {
        $this->pimpleAdapter->addInstanceLazyLoad($this->builder, Foo::class);

        self::assertTrue(is_callable(InstanceHelper::getProtectedProperty($this->container, 'values')[Foo::class]));
    }

    /**
     * @test
     *
     * @small
     */
    public function getInstance_ShouldSetInstanceInContainer()
    {
        $this->pimpleAdapter->addInstance(new Foo);

        self::assertInstanceOf(Foo::class, $this->pimpleAdapter->getInstance(Foo::class));
    }

    /**
     * @test
     *
     * @small
     */
    public function hasInstance_ShouldReturnFalse()
    {
        self::assertFalse($this->pimpleAdapter->hasInstance(Foo::class));
    }

    /**
     * @test
     *
     * @small
     */
    public function hasInstance_ShouldReturnTrue()
    {
        $this->pimpleAdapter->addInstance(new Foo);

        self::assertTrue($this->pimpleAdapter->hasInstance(Foo::class));
    }

    /**
     * @test
     *
     * @small
     *
     * @expectedException \Hgraca\MicroDI\Adapter\Exception\ClassOrInterfaceNotFoundException
     */
    public function hasInstance_ShouldThrowExceptionIfNotClassOrInterface()
    {
        $this->pimpleAdapter->hasInstance(Foo::class . '\dummyClass');
    }

    /**
     * @test
     *
     * @small
     */
    public function getInstance_ShouldGetInstance()
    {
        $this->pimpleAdapter->addInstance(new Foo);
        self::assertFalse(is_callable(InstanceHelper::getProtectedProperty($this->container, 'values')[Foo::class]));

        self::assertInstanceOf(Foo::class, $this->pimpleAdapter->getInstance(Foo::class));
    }

    /**
     * @test
     *
     * @small
     */
    public function getInstance_ShouldGetInstanceFromCallable()
    {
        $this->pimpleAdapter->addInstanceLazyLoad($this->builder, Foo::class);
        self::assertTrue(is_callable(InstanceHelper::getProtectedProperty($this->container, 'values')[Foo::class]));

        self::assertInstanceOf(Foo::class, $this->pimpleAdapter->getInstance(Foo::class));
    }

    /**
     * @test
     *
     * @small
     *
     * @expectedException \Hgraca\MicroDI\Adapter\Exception\ClassOrInterfaceNotFoundException
     */
    public function getInstance_ShouldThrowExceptionIfNotClassOrInterface()
    {
        $this->pimpleAdapter->getInstance(Foo::class . '\dummyClass');
    }

    /**
     * @test
     *
     * @small
     *
     * @expectedException \Hgraca\MicroDI\Adapter\Exception\InstanceNotFoundException
     */
    public function getInstance_ShouldThrowExceptionIfNotFound()
    {
        $this->pimpleAdapter->getInstance(Foo::class);
    }
}
