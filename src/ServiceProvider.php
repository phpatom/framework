<?php


namespace Atom\Framework;

use Atom\Framework\Contracts\ServiceProviderContract;

abstract class ServiceProvider implements ServiceProviderContract
{
    public static function fromCallable(callable $callable): CallableServiceProvider
    {
        return new CallableServiceProvider($callable);
    }
}
