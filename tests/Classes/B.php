<?php

namespace Yekhlakov\Proxy\Tests\Classes;

/**
 * Class to be used as a default proxy backend
 */
class B
{
    public static int $instanceCount = 0;

    public function __construct(private int $base)
    {
        self::$instanceCount++;
    }

    /**
     * This method will be called from the test
     */
    public function test(int $x): int
    {
        return $this->base + $x;
    }
}
