<?php

namespace Yekhlakov\Proxy\Tests;

use PHPUnit\Framework\TestCase;
use Yekhlakov\Proxy\Tests\Classes\Ab;
use Yekhlakov\Proxy\Tests\Classes\Ac;
use Yekhlakov\Proxy\Tests\Classes\B;
use Yekhlakov\Proxy\Tests\Classes\C;

class ProxyTest extends TestCase
{
    public function testGeneral(): void
    {
        // Testing mocks:

        // Apply some mocks
        Ab::mock('test', 12345); // This exists on the backend, and is explicitly declared in the proxy class as a stub.
        Ab::mock('kek', 54321); // This does not exist at all.

        $this->assertSame(12345, Ab::test(1)); // This is mocked
        $this->assertSame(54321, Ab::kek()); // This is mocked
        // Calling only mocked methods does not lead to automatic backend initialization
        $this->assertSame(0, B::$instanceCount);

        // Testing proxied methods

        // Remove the mock from the method that exists on the backend
        Ab::unmock('test');
        $this->assertSame(667, Ab::test(1)); // This is now not mocked, so we get a real result
        // Calling the unmocked method results in backend initialization
        $this->assertSame(1, B::$instanceCount);
        $this->assertSame(54321, Ab::kek()); // This is still mocked

        // Unmock all methods
        Ab::unmock();
        $this->assertSame(null, Ab::kek()); // This is now not mocked but does not exist on a backend, so we get null

        // Testing backend switching

        $newBackend = new C();
        $oldBackend = Ab::useBackend($newBackend);
        // Check that our previous backend is correct
        $this->assertInstanceOf(B::class, $oldBackend);

        $this->assertSame(-1, Ab::test(1)); // This method on the new backend returns different value.
        $this->assertSame('KEK', Ab::kek()); // This is now a real method on the new backend.

        // Switch back to old backend
        $newestBackend = Ab::useBackend($oldBackend);

        // Check that we received exactly the backend we registered.
        $this->assertSame($newBackend, $newestBackend);

        // Confirm that the backend is now the one that was registered automatically.
        $this->assertSame(667, Ab::test(1));
        $this->assertSame(null, Ab::kek());

        // Fake/wrong backend
        Ab::useBackend((object) []);
        // No methods exist on our backend at all now, so we get only nulls
        $this->assertSame(null, Ab::kek());
        // The 'test' method has not-nullable return type, so we get a TypeError
        $this->expectException(\TypeError::class);
        $this->assertSame(null, Ab::test(1));
    }

    public function testCreateBackend(): void
    {
        $this->assertSame(-666, Ac::test(666));
        $this->assertSame('KEK', Ac::kek());

        // REMOVE the backend from the proxy
        $oldBackend = Ac::useBackend();
        $this->assertInstanceOf(C::class, $oldBackend);

        // As we now have no backend, all methods will return null
        $this->assertSame(null, Ac::test(666));
        $this->assertSame(null, Ac::kek());
    }
}
