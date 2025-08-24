<?php

namespace Yekhlakov\Proxy;

use Yekhlakov\Proxy\Attributes\Backend;

/**
 * Proxy
 *
 * Should be extended by actual proxy classes, so it is abstract.
 */
abstract class Proxy
{
    // Backends for proxy "instances"
    // (the backends are kept wrapped in anonymous classes)
    private static array $backendWrappers = [];

    /**
     * Get the backend associated with the current proxy "instance"
     */
    private static function getBackendWrapper(): object
    {
        return self::$backendWrappers[static::class] ??= new class (static::class) {
            private bool $isInitialized = false;
            private ?object $backend = null;
            private array $mocks = [];
            private ?string $backendClass = null;
            private array $backendConstructorArgs = [];

            public function __construct(private string $proxyClass)
            {
                /** @var ?Backend $backendAttribute */
                $backendAttribute = ((new \ReflectionClass($this->proxyClass))->getAttributes(Backend::class)[0] ?? null)?->newInstance();
                $this->backendClass = $backendAttribute->className ?? null;
                $this->backendConstructorArgs = $backendAttribute->constructorArgs ?? [];
            }

            /**
             * Try to initialize the backend
             */
            public function init(): bool
            {
                if (!empty($this->isInitialized)) {
                    // A backend object is already attached, so do nothing
                    return true;
                }

                // Prevent repeated initializations.
                // We use separate flag instead of emptiness check because null backend is ok.
                $this->isInitialized = true;

                $className = $this->proxyClass;

                if (\method_exists($className, 'createBackend')) {
                    // A static method constructing a backend is declared in the proxy "instance" class, so call it.
                    return !empty($this->backend = $className::createBackend());
                }

                if ($this->backendClass)
                {
                    // A backend class was specified via Backend attribute, so construct it from the params
                    // retrieved from the attribute
                    $backendClass = $this->backendClass;

                    $this->backend = new $backendClass(...$this->backendConstructorArgs);

                    return true;
                }

                return false;
            }

            /**
             * Use the given object (or null) as a backend. Return the previously attached backend.
             */
            public function useBackend(?object $newBackend = null): ?object
            {
                $oldBackend = $this->backend;
                $this->backend = $newBackend;
                $this->isInitialized = true;

                return $oldBackend;
            }

            /**
             * If the method is currently mocked, call the mock.
             * Return null immediately:
             * - if we don't have any backend registered;
             * - if there is no such method on the backend.
             * Otherwise, call the required method on the backend.
             */
            public function call(string $methodName, array $methodArgs = []): mixed
            {
                if ($this->isMethodMocked($methodName)) {
                    return $this->callMock($methodName, $methodArgs);
                }

                // Try to initialize the backend (if possible and if it is not yet initialized)
                $this->init();

                if (!$this->backend || !\method_exists($this->backend, $methodName)) {
                    // No backend is attached or no such method exists on backend - return null
                    return null;
                }

                // Call the method on the backend
                return $this->backend->{$methodName}(...$methodArgs);
            }

            /**
             * Check if the given method is mocked
             */
            private function isMethodMocked(string $methodName): bool
            {
                return \array_key_exists($methodName, $this->mocks);
            }

            /**
             * Actually call a mocked method.
             */
            private function callMock(string $methodName, array $args): mixed
            {
                // Get the mock value
                $mock = $this->mocks[$methodName] ?? null;

                // If it is registered as a callable, call it and return its return value
                if (\is_callable($mock)) {
                    return $mock(...$args);
                }

                // Otherwise return the mock value itself
                return $mock;
            }

            /**
             * Register a mockery method.
             * $val may be either a value which will be returned by the method
             * or a callable which will be invoked on the mockery method call.
             */
            public function mock(string $methodName, mixed $val): void
            {
                $this->mocks[$methodName] = $val;
            }

            /**
             * Unregister a mockery method (or all methods, if the name is empty).
             */
            public function unmock(?string $methodName = null): void
            {
                if (empty($methodName)) {
                    $this->mocks = [];
                } else {
                    unset($this->mocks[$methodName]);
                }
            }
        };
    }

    /**
     * A helper to route calls from inside method stubs.
     * This has to have as short a name as possible to keep stub methods tidy.
     */
    protected static function _(): mixed
    {
        // Extract the called method name and argument list
        [
            'function' => $methodName,
            'args'     => $methodArgs,
        ] = \debug_backtrace(limit: 2)[1];

        return self::getBackendWrapper()->call($methodName, $methodArgs);
    }

    /**
     * The main router must be public.
     */
    public static function __callStatic(string $methodName, array $methodArgs): mixed
    {
        return self::getBackendWrapper()->call($methodName, $methodArgs);
    }

    /**
     * Methods after this line constitute the public control interface for the proxy.
     * They are used to modify its behavior in runtime.
     */

    /**
     * Use the given object as the backend. Return the previously attached backend.
     */
    public static function useBackend(?object $newBackend = null): ?object
    {
        return self::getBackendWrapper()->useBackend($newBackend);
    }

    /**
     * Register a mockery method.
     * $val may be either a value which will be returned by the method
     * or a callable which will be invoked on the mockery method call.
     * Note that the mocked methods are stored on the backend wrapper!
     */
    public static function mock(string $methodName, mixed $val = null): void
    {
        self::getBackendWrapper()->mock($methodName, $val);
    }

    /**
     * Unregister a mockery method (or all methods, if the name is empty).
     */
    public static function unmock(?string $methodName = null): void
    {
        self::getBackendWrapper()->unmock($methodName);
    }
}
