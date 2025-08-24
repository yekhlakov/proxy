## proxy

A simple lightweight proxy for php.

The proxy is a class that receives static method calls and forwards them to methods with the same name
on a specified backend object.

The backends can be switched at runtime.

Methods can be replaced with mocks at runtime (even those missing on the backend).

### Simplest use case

Define the proxy and its backend (via `Backend` attribute. The attribute also allows
to provide parameters to backend constructor)

    #[Backend(XBackend::class, 123, true)]
    class MyProxy extends Proxy
    {
        // Proxied methods go here.
        // (Note that method bodies are plain stubs).
        // Real methods instead of phpdoc annotations provide real type safety.
        public static function f(int $x, ?int $y = null): int { return self::_(); }
	    public static function g(string | array $x): string { return self::_(); }
	    public static function h(): void { self::_(); }
        // Feel free to call undefined methods, they will get proxied too.
        // Phpdoc-declared methods are okay as well.
    }

Then use the proxy

    $a = MyProxy::f(1, 2);

The proxy will construct its default backend (`XBackend` in the example above), call the method `f` on it, and return
the result.

### Complex backend construction

Sometimes it may not be sufficient to have a backend constructed from constant parameters.
In such case we can define a factory method in the proxy class:

    public static function createBackend(): XBackend
    {
        ...
    }

This method must return an object to be used as backend. This method overrides the `Backend` attribute.

In either case the default backend will be constructed only when it is needed (namely upon first call
of a method to be proxied to the backend). 

### Use specific backend

Instead of (or along with) defining a default backend, we may explicitly
attach a backend class to our proxy at runtime:

    $oldBackend = MyProxy::use(new YBackend());

Or even remove a backend:

    MyProxy::use(null);
    // now all calls to MyProxy::someMethod will return null if someMethod is not mocked (see later)


### Mock methods

We may "mock" methods on the proxy. Such mocked methods will not be proxied to the backend.

Mocked methods may return a value:

    MyProxy::mock('ololo', 666);
    MyProxy::ololo(); // returns 666

It is also possible to attach a callable as the mocked method:

    MyProxy::mock('ururu', fn ($x) => [$x + $x]);
    MyProxy::ururu(333); // returns [666]

A method may be unmocked if necessary:

    MyProxy::unmock('ololo'); // do not want it anymore
    MyProxy::ololo(); // returns null now

Mocks do not require a backend to work.

So if we just add a mock to a proxy and then call it, and no other interactions with proxy have occurred yet,
the default backend will not have been constructed at all.

### Why is it a proxy, not a facade or a decorator?

Because all it does (yet) is forwarding the calls without complex processing. (Mocks do not count as complex).
It does not (or is not intended to) reduce the complexity of underlying backend, also it does not change the data
passing to and from the backend.
