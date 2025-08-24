<?php

namespace Yekhlakov\Proxy\Tests\Classes;

use Yekhlakov\Proxy\Proxy;


/**
 * A proxy class having C as the default backend.
 * The backend is constructed via `createBackend` method.
 * Proxied methods are declared here in docblock:
 * @method static int test (int $x)
 * @method static string kek ()
 */
class Ac extends Proxy
{
    public static function createBackend(): ?C
    {
        return new C();
    }
}
