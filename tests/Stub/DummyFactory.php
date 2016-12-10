<?php

namespace Hgraca\MicroDI\Test\Stub;

use Hgraca\MicroDI\FactoryInterface;

final class DummyFactory implements FactoryInterface
{
    /**
     * @return mixed
     */
    public function create(array $factoryContext = [])
    {
        $class = $factoryContext['class'] ?? Dummy::class;

        return new $class;
    }
}
