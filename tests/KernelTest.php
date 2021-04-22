<?php


namespace Atom\Framework\Test;

use Atom\DI\Container;
use Atom\Event\Contracts\EventListenerContract;
use Atom\Event\EventDispatcher;
use Atom\Framework\Contracts\HasKernel;
use Atom\Framework\Contracts\ServiceProviderContract;
use Atom\Framework\Env;
use Atom\Framework\Events\ServiceProviderRegistered;
use Atom\Framework\Events\ServiceProviderRegistrationFailure;
use Atom\Framework\Exceptions\AppAlreadyBootedException;
use Atom\Framework\Kernel;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class KernelTest extends TestCase
{

    public function testConstructor()
    {
        $kernel = new Kernel(__DIR__);
        $this->assertInstanceOf(Container::class, $kernel->container());
        $this->assertInstanceOf(EventDispatcher::class, $kernel->eventDispatcher());
        $this->assertInstanceOf(Env::class, $kernel->env());
        $this->assertEquals(Env::DEV, (string)$kernel->env());
        $this->assertEquals(__DIR__, $kernel->appPath());

        $kernel = new Kernel(
            __DIR__,
            $env = Env::TESTING,
            $container = new Container(),
            $eventDispatcher = new EventDispatcher()
        );
        $this->assertEquals((string)$kernel->env(), $env);
        $this->assertEquals($container, $kernel->container());
        $this->assertEquals($eventDispatcher, $kernel->eventDispatcher());

        $this->assertInstanceOf(HasKernel::class, $kernel);
    }

    public function testItProvideUtils()
    {
        $kernel = new Kernel(
            __DIR__,
            $env = Env::TESTING,
            $container = new Container(),
            $eventDispatcher = new EventDispatcher()
        );
        $this->assertEquals($container, $kernel->get(ContainerInterface::class));
        $this->assertEquals($container, $kernel->get(Container::class));

        $this->assertEquals($eventDispatcher, $kernel->get(EventDispatcher::class));
        $this->assertEquals($eventDispatcher, $kernel->get(EventDispatcherInterface::class));
        $this->assertInstanceOf(Env::class, $kernel->env());
        $this->assertEquals(Env::TESTING, (string)$kernel->env());
    }

    public function testEnv()
    {
        $kernel = new kernel(__DIR__, Env::TESTING);
        $this->assertInstanceOf(Env::class, $kernel->env());
        $this->assertEquals(Env::TESTING, (string)$kernel->env());
    }

    public function testGet()
    {
        $container = new Container();
        $container->bind("foo")->toValue("bar");
        $kernel = new Kernel(__DIR__, Env::DEV, $container);
        $kernel->container()->bind("bar", "baz");
        $this->assertEquals("baz", $kernel->get("bar"));
        $this->assertEquals("bar", $kernel->get("foo"));
    }

    public function testAppPath()
    {
        $kernel = new Kernel("foo");
        $this->assertEquals("foo", $kernel->appPath());
    }

    public function testContainer()
    {
        $kernel = new Kernel("foo");
        $this->assertInstanceOf(ContainerInterface::class, $kernel->container());
        $this->assertInstanceOf(Container::class, $kernel->container());
    }

    public function testEventDispatcher()
    {
        $kernel = new Kernel("foo");
        $this->assertInstanceOf(EventDispatcher::class, $kernel->eventDispatcher());
    }

    public function testServiceProviderCannotBeUsedIfTheKernelHasBooted()
    {
        $kernel = new Kernel("foo");
        $kernel->boot();
        $this->expectException(AppAlreadyBootedException::class);
        /**
         * @var ServiceProviderContract $mock
         */
        $mock = $this->getMockBuilder(ServiceProviderContract::class)->getMock();
        $kernel->use($mock);
    }

    public function testAServiceCannotBeRegisteredMoreThanOnce()
    {
        $kernel = new Kernel("foo");
        $mock = $this->getMockBuilder(ServiceProviderContract::class)->getMock();
        $mock->expects($this->once())->method("register");
        $kernel->use($mock);
        $kernel->use($mock);
        $kernel->use($mock);
    }

    public function testServiceProviderAreRegistered()
    {
        $mock = $this->getMockBuilder(ServiceProviderContract::class)->getMock();
        $mock->expects($this->once())->method("register");
        $kernel = new Kernel("foo", null, null, $dispatcher = new EventDispatcher());
        $listener = $this->getMockBuilder(EventListenerContract::class)->getMock();
        $listener->method("canBeCalled")->willReturn(true);
        $listener->expects($this->once())->method("handle");
        $dispatcher->addEventListener(ServiceProviderRegistered::class, $listener);
        $kernel->use($mock);
        $this->assertContains(get_class($mock), $kernel->getRegisteredProviders());
    }

    public function testEventIsEmittedIfServiceProviderRegistrationFails()
    {
        $mock = $this->getMockBuilder(ServiceProviderContract::class)->getMock();
        $mock->method("register")->willThrowException(new \Exception());
        $listener = $this->getMockBuilder(EventListenerContract::class)->getMock();
        $listener->method("canBeCalled")->willReturn(true);
        $listener->expects($this->once())->method("handle");

        $kernel = new Kernel("foo", null, null, $dispatcher = new EventDispatcher());
        $dispatcher->addEventListener(ServiceProviderRegistrationFailure::class, $listener);
        $this->expectException(\Exception::class);
        $kernel->use($mock);
    }

    public function testProviderRegistered()
    {
        $mock = $this->getMockBuilder(ServiceProviderContract::class)->getMock();
        $kernel = new Kernel("foo");
        $kernel->use($mock);
        $this->assertTrue($kernel->providerRegistered(get_class($mock)));
    }

    public function testProviders()
    {
        $mock1 = $this->getMockBuilder(ServiceProviderContract::class)->getMock();
        $mock2 = $this->getMockBuilder(ServiceProviderContract::class)->getMock();
        $mock3 = $this->getMockBuilder(ServiceProviderContract::class)->getMock();
        $kernel = new Kernel("foo");
        $kernel->providers([$mock1, $mock2, $mock3]);
        $this->assertTrue($kernel->providerRegistered(get_class($mock1)));
        $this->assertTrue($kernel->providerRegistered(get_class($mock2)));
        $this->assertTrue($kernel->providerRegistered(get_class($mock3)));
    }

    public function testYouCannotBootTwice()
    {
        $this->expectException(AppAlreadyBootedException::class);
        $kernel = new Kernel("foo");
        $kernel->boot();
        $kernel->boot();
    }

    public function testBoot()
    {
        $kernel = new Kernel("foo");
        $kernel->boot();
        $this->assertTrue($kernel->hasBooted());
    }

    public function testMetaDataCannotBeDefineIfKernelHasAlreadyBooted()
    {
        $this->expectException(AppAlreadyBootedException::class);
        $kernel = new Kernel("foo");
        $kernel->boot();
        $kernel->setMetaData("FOO", "BAR");
    }

    public function testMetaData()
    {
        $kernel = new Kernel("foo");
        $kernel->setMetaData("FOO", "BAR");
        $this->assertEquals("BAR", $kernel->getMetaData("FOO"));
        $this->assertEquals("BAZ", $kernel->getMetaData("BAR", "BAZ"));
    }

    public function testGetKernel()
    {
        $kernel = new Kernel("foo");
        $this->assertEquals($kernel->getKernel(), $kernel);
    }

    public function testGetRegisteredProviders()
    {
        $mock1 = $this->getMockBuilder(ServiceProviderContract::class)->getMock();
        $kernel = new Kernel("foo");
        $kernel->providers([$mock1]);
        $this->assertEquals(
            [get_class($mock1)],
            $kernel->getRegisteredProviders()
        );
    }
}
