<?php

namespace Hgraca\MicroDI\Test;

use Hgraca\MicroDI\BuilderInterface;
use Hgraca\MicroDI\Executor;
use Hgraca\MicroDI\Test\Stub\Foo;
use Mockery;
use PHPUnit_Framework_TestCase;

final class ExecutorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     *
     * @small
     */
    public function shouldExecuteCallable()
    {
        $name = 'AAA';
        $builderMock = Mockery::mock(BuilderInterface::class);
        $builderMock->shouldReceive('buildDependencies')->once()->andReturn([$name]);
        $dependencyInjector = new Executor($builderMock);

        self::assertEquals(sprintf(Foo::PATTERN, $name), $dependencyInjector->execute([Foo::class, 'test'], []));
    }
}
