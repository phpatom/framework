<?php


namespace Atom\Framework\Test;

use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\DI\Exceptions\UnsupportedInvokerException;
use Atom\Event\AbstractEventListener;
use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;
use Atom\Framework\ApplicationFactory;
use Atom\Framework\Contracts\ServiceProviderContract;
use Atom\Framework\Kernel;
use Atom\Routing\Router;
use Atom\Framework\Application;
use Atom\Framework\Contracts\ModuleContract;
use Atom\Framework\Events\ServiceProviderRegistrationFailed;
use Atom\Framework\Exceptions\RequestHandlerException;
use Atom\Framework\Http\RequestHandler;
use Atom\Framework\WebServiceProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;
use Throwable;

class ApplicationTest extends TestCase
{
    /**
     * @throws StorageNotFoundException
     * @throws Throwable
     * @throws UnsupportedInvokerException
     * @throws ListenerAlreadyAttachedToEvent
     */
    public function testItIsCreatedWIthWebServiceProviderWhenUsingNamedConstructor()
    {
        $app = Application::create(__DIR__);
        $this->assertTrue($app->kernel()->providerRegistered(WebServiceProvider::class));
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws StorageNotFoundException
     * @throws Throwable
     * @throws UnsupportedInvokerException
     */
    public function testItNamedConstructors()
    {
        $app = Application::dev(__DIR__);
        $this->assertTrue($app->env()->isDev());

        $app = Application::prod(__DIR__);
        $this->assertTrue($app->env()->isProduction());

        $app = Application::test(__DIR__);
        $this->assertTrue($app->env()->isTesting());

        $app = Application::staging(__DIR__);
        $this->assertTrue($app->env()->isStaging());

        $this->assertInstanceOf(ApplicationFactory::class, Application::with());
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws RequestHandlerException
     * @throws StorageNotFoundException
     * @throws Throwable
     * @throws UnsupportedInvokerException
     */
    public function testRun()
    {
        $mock = $this->getMockBuilder(RequestHandler::class)->disableOriginalConstructor()->getMock();
        $mock->expects($this->once())->method("run");
        $mock->method("run")->willReturn(null);
        $app = Application::with()
            ->requestHandler($mock)
            ->create(__DIR__);
        $app->run();
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws StorageNotFoundException
     * @throws Throwable
     * @throws UnsupportedInvokerException
     */
    public function testAServiceProviderCanBeUsed()
    {
        $app = Application::create(__DIR__);
        $provider = new class implements ServiceProviderContract {
            public function register(Kernel $kernel)
            {
                $kernel->container()->bindings()->store("foo", $kernel->container()->as()->value("bar"));
            }
        };
        $app->use($provider);
        $loaded = $app->kernel()->getRegisteredProviders();
        $this->assertEquals("bar", $app->container()->get("foo"));
        $app->use($provider);
        $this->assertEquals($loaded, $app->kernel()->getRegisteredProviders());
    }

    /**
     * @throws Throwable
     */
    public function testAnEventIsEmittedWhenItFailsToUseAServiceProvider()
    {
        $app = Application::create(__DIR__);
        $this->expectException(RuntimeException::class);
        $app->use(new class implements ServiceProviderContract {
            public function register(Kernel $kernel)
            {
                throw new RuntimeException("Sike!");
            }
        });
        $listener = new class extends AbstractEventListener {
            public function called(): bool
            {
                return $this->calls == 1;
            }

            public function on($event): void
            {
            }
        };
        $app->eventDispatcher()->addEventListener(
            ServiceProviderRegistrationFailed::class,
            $listener
        );
        $app->kernel()->boot();
        $this->assertTrue($listener->called());
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws StorageNotFoundException
     * @throws Throwable
     * @throws UnsupportedInvokerException
     */
    public function testTheRequestHandlerCanBeRetrieve()
    {
        $app = Application::create(__DIR__);
        $this->assertInstanceOf(RequestHandler::class, $app->requestHandler());
        $this->assertEquals($app->requestHandler(), $app->requestHandler());
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws StorageNotFoundException
     * @throws Throwable
     * @throws UnsupportedInvokerException
     */
    public function testTheRouterCanBeRetrieve()
    {
        $app = Application::create(__DIR__);
        $this->assertInstanceOf(Router::class, $app->router());
        $this->assertEquals($app->router(), $app->router());
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws StorageNotFoundException
     * @throws Throwable
     * @throws UnsupportedInvokerException
     */
    public function testModulesAreAdded()
    {
        $module1 = $this->getMockClass(ModuleContract::class);
        $module2 = $this->getMockClass(ModuleContract::class);
        $modules = [$module1, $module2];
        $mock = $this->getMockBuilder(RequestHandler::class)->disableOriginalConstructor()->getMock();
        $mock->expects($this->exactly(1))->method("withModules")->with($modules);
        $app = Application::with()->requestHandler($mock)->create(__DIR__);
        $app->withModules($modules);
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws StorageNotFoundException
     * @throws Throwable
     * @throws UnsupportedInvokerException
     */
    public function testAModuleCanBeAdded()
    {
        $module = $this->getMockClass(ModuleContract::class);
        $mock = $this->getMockBuilder(RequestHandler::class)->disableOriginalConstructor()->getMock();
        $mock->expects($this->exactly(1))->method("withModule")->with($module);
        $app = Application::with()->requestHandler($mock)->create(__DIR__);
        $app->withModule($module);
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws StorageNotFoundException
     * @throws Throwable
     * @throws UnsupportedInvokerException
     */
    public function testRouteGroupAreRegistered()
    {
        $router = $this->getMockBuilder(Router::class)->getMock();
        $prefix = "";
        $callable = function () {
        };
        $router->expects($this->once())->method("group")->with($prefix, $callable, null);
        $app = Application::with()->router($router)->create("");
        $app->group($prefix, $callable);
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws RequestHandlerException
     * @throws StorageNotFoundException
     * @throws Throwable
     * @throws UnsupportedInvokerException
     */
    public function testMiddlewareCanBeAdded()
    {
        $middleware = $this->getMockClass(MiddlewareInterface::class);
        $mock = $this->getMockBuilder(RequestHandler::class)->disableOriginalConstructor()->getMock();
        $mock->expects($this->exactly(1))->method("add")->with($middleware);
        $app = Application::with()->requestHandler($mock)->create(__DIR__);
        $app->add($middleware);
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws RequestHandlerException
     * @throws StorageNotFoundException
     * @throws Throwable
     * @throws UnsupportedInvokerException
     */
    public function testMiddlewareCanBeLoaded()
    {
        $middleware = $this->getMockClass(MiddlewareInterface::class);
        $mock = $this->getMockBuilder(RequestHandler::class)->disableOriginalConstructor()->getMock();
        $mock->expects($this->exactly(1))->method("load")->with($middleware);
        $app = Application::with()->requestHandler($mock)->create(__DIR__);
        $app->load($middleware);
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws StorageNotFoundException
     * @throws Throwable
     * @throws UnsupportedInvokerException
     */
    public function testGetProviderLoaded()
    {
        $app = Application::create(__DIR__);
        $providers = [WebServiceProvider::class];
        $this->assertEquals($providers, $app->getRegisteredProviders());
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws StorageNotFoundException
     * @throws Throwable
     * @throws UnsupportedInvokerException
     */
    public function testProvidersCanBeAdded()
    {
        $app = Application::create(__DIR__);
        $providers = [WebServiceProvider::class];
        $provider1 = $this->createMock(ServiceProviderContract::class);
        $provider2 = $this->getMockBuilder(ServiceProviderContract::class)->getMock();

        $app->providers([$provider1, $provider2]);
        $this->assertEquals(array_merge($providers, [
            get_class($provider1),
            get_class($provider2)
        ]), $app->kernel()->getRegisteredProviders());
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws StorageNotFoundException
     * @throws Throwable
     * @throws UnsupportedInvokerException
     */
    public function testOf()
    {
        $app = Application::create(__DIR__);
        $kernel = $app->kernel();
        $requestHandler = $app->requestHandler();

        $clone = Application::of($kernel);
        $this->assertEquals($clone->router(), $app->router());
        $this->assertEquals($clone->requestHandler(), $app->requestHandler());
        $this->assertEquals($clone->env(), $app->env());

        $clone2 = Application::of($requestHandler);
        $this->assertEquals($clone2->router(), $app->router());
        $this->assertEquals($clone2->requestHandler(), $app->requestHandler());
        $this->assertEquals($clone2->env(), $app->env());
    }
}
