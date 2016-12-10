<?php

namespace Hgraca\MicroDI\Test\Stub;

final class DummyUser
{
    /**
     * @var Dummy
     */
    private $dummy;

    public function __construct(Dummy $dummy)
    {
        $this->dummy = $dummy;
    }
}
