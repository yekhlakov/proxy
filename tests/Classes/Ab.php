<?php

namespace Yekhlakov\Proxy\Tests\Classes;

use Yekhlakov\Proxy\Attributes\Backend;
use Yekhlakov\Proxy\Proxy;

/**
 * A proxy class having B as the default backend.
 * The backend is specified via `Backend` attribute.
 */
#[Backend(B::class, 666)]
class Ab extends Proxy
{
    /*
     * Proxied method
     */
    public static function test(int $x): int
    {
        // This is all we need here.
        return self::_();
    }
}
