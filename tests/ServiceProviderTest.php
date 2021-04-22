<?php


namespace Atom\Framework\Test;

use Atom\Framework\CallableServiceProvider;
use Atom\Framework\ServiceProvider;
use PHPUnit\Framework\TestCase;

class ServiceProviderTest extends TestCase
{

    public function testFromCallable()
    {
        $this->assertInstanceOf(
            CallableServiceProvider::class,
            ServiceProvider::fromCallable(function () {
            })
        );
        $func = function () {
        };
        $this->assertEquals($func, ServiceProvider::fromCallable($func)->getCallable());
    }
}
