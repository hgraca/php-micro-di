<?php

namespace Hgraca\MicroDI\Test\Stub;

final class Bar
{
    const PATTERN = 'Hello %s, from BAR!';

    /**
     * @var Foo
     */
    private $foo;

    /**
     * @var string
     */
    private $someText;

    /**
     * @var DummyUser
     */
    private $dummyUser;

    /**
     * @var string
     */
    private $givenArg;

    public function __construct(Foo $foo, string $someText, DummyUser $dummyUser, string $givenArg)
    {
        $this->foo = $foo;
        $this->someText = $someText;
        $this->dummyUser = $dummyUser;
        $this->givenArg = $givenArg;
    }

    public static function test($name)
    {
        return sprintf(self::PATTERN, $name);
    }
}
