<?php
namespace Hgraca\MicroDi\Factory\Contract;

interface FactoryInterface
{
    /**
     * Instantiates a class based on the current application context.
     *
     * @param array $factoryContext
     *
     * @return mixed
     */
    public function create(array $factoryContext = []);
}
