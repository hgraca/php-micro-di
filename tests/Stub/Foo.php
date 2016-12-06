<?php

namespace Hgraca\MicroDI\Test\Stub;

final class Foo
{
    const PATTERN = 'Hello %s!';

    public static function test($name)
    {
        return sprintf(self::PATTERN, $name);
    }
}
