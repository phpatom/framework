<?php


namespace Atom\Framework\Test;

use Atom\Framework\CallableServiceProvider;
use Atom\Framework\Kernel;
use PHPUnit\Framework\TestCase;

class CallableServiceProviderTest extends TestCase
{
    public function testGetCallable()
    {
        $func = function () {
        };
        $this->assertEquals($func, (new CallableServiceProvider($func))->getCallable());
    }

    public function testItIsRegistered()
    {
        $kernel = new Kernel("foo");
        $this->assertFalse($kernel->hasMetaData("foo"));
        $kernel->use(new CallableServiceProvider(function (Kernel $kernel) {
            $kernel->setMetaData("foo", "bar");
        }));
        $this->assertTrue($kernel->hasMetaData("foo"));
        $this->assertEquals("bar", $kernel->getMetaData("foo"));
    }
}
