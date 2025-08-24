<?php

namespace Yekhlakov\Proxy\Tests\Classes;

/**
 * Class to be used as a proxy backend
 */
class C
{
    public function test(int $x): int
    {
        return -$x;
    }

    public function kek(): string
    {
        return 'KEK';
    }
}
