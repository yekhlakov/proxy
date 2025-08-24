<?php

namespace Yekhlakov\Proxy\Attributes;

/**
 * Attribute to specify default backend of a proxy
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Backend
{
    public array $constructorArgs;

    public function __construct(public string $className, ...$constructorArgs)
    {
        $this->constructorArgs = $constructorArgs;
    }
}
