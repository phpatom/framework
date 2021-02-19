<?php


namespace Atom\Web\Test;

use Atom\DI\DIC;
use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\Event\AbstractEventListener;
use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;
use Atom\Kernel\Contracts\ServiceProviderContract;
use Atom\Kernel\Events\EventServiceProvider;
use Atom\Kernel\FileSystem\DiskManagerProvider;
use Atom\Kernel\FileSystem\PathProvider;
use Atom\Kernel\Kernel;
use Atom\Routing\Contracts\RouterContract;
use Atom\Routing\Router;
use Atom\Web\Application;
use Atom\Web\Contracts\ModuleContract;
use Atom\Web\Events\ServiceProviderFailed;
use Atom\Web\Exceptions\RequestHandlerException;
use Atom\Web\Http\RequestHandler;
use Atom\Web\WebServiceProvider;
use http\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;

class ApplicationTest extends TestCase
{
    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     * @throws ListenerAlreadyAttachedToEvent
     */
    public function testItIsCreatedWIthWebServiceProviderWhenUsingNamedConstructor()
    {
        $app = Application::create(__DIR__);
        $this->assertContains(WebServiceProvider::class, $app->getProvidersLoaded());
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    public function testItNamedConstructors()
    {
        $app = Application::dev(__DIR__);
        $this->assertTrue($app->env()->isDev());

        $app = Application::prod(__DIR__);
        $this->assertTrue($app->env()->isProduction());

        $app = Application::test(__DIR__);
        $this->assertTrue($app->env()->isTesting());

        $this->assertInstanceOf(WebServiceProvider::class, Application::with());
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws StorageNotFoundException
     * @throws RequestHandlerException
     */
    public function testRun()
    {
        $mock = $this->getMockBuilder(RequestHandler::class)->disableOriginalConstructor()->getMock();
        $mock->expects($this->once())->method("run");
        $mock->method("run")->willReturn(null);
        $app = Application::with()->requestHandler($mock)->create(__DIR__);
        $app->run();
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    public function testAServiceProviderCanBeUsed()
    {
        $app = Application::create(__DIR__);
        $provider = new class implements ServiceProviderContract {
            public function register(Kernel $app)
            {
                $app->container()->bindings()->store("foo", $app->container()->as()->value("bar"));
            }
        };
        $app->use($provider);
        $loaded = $app->getProvidersLoaded();
        $this->assertEquals("bar", $app->container()->get("foo"));
        $app->use($provider);
        $this->assertEquals($loaded, $app->getProvidersLoaded());
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    public function testAnEventIsEmittedWhenItFailsToUseAServiceProvider()
    {
        $app = Application::create(__DIR__);
        $this->expectException(\RuntimeException::class);
        $app->use(new class implements ServiceProviderContract {
            public function register(Kernel $app)
            {
                throw new \RuntimeException("Sike!");
            }
        });
        $listener = new class extends AbstractEventListener {
            public function called()
            {
                return $this->calls == 1;
            }

            public function on($event): void
            {
            }
        };
        $app->eventDispatcher()->addEventListener(
            ServiceProviderFailed::class,
            $listener
        );
        $this->assertTrue($listener->called());
    }

    public function testTheRequestHandlerCanBeRetrieve()
    {
        $app = Application::create(__DIR__);
        $this->assertInstanceOf(RequestHandler::class, $app->requestHandler());
        $this->assertEquals($app->requestHandler(), $app->requestHandler());
    }

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
     */
    public function testAModuleCanBeAdded()
    {
        $module = $this->getMockClass(ModuleContract::class);
        $mock = $this->getMockBuilder(RequestHandler::class)->disableOriginalConstructor()->getMock();
        $mock->expects($this->exactly(1))->method("withModule")->with($module);
        $app = Application::with()->requestHandler($mock)->create(__DIR__);
        $app->withModule($module);
    }

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
     * @throws StorageNotFoundException
     * @throws RequestHandlerException
     */
    public function testMiddlewareCanBeAdded()
    {
        $middleware = $this->getMockClass(MiddlewareInterface::class);
        $mock = $this->getMockBuilder(RequestHandler::class)->disableOriginalConstructor()->getMock();
        $mock->expects($this->exactly(1))->method("add")->with($middleware);
        $app = Application::with()->requestHandler($mock)->create(__DIR__);
        $app->add($middleware);
    }

    /* @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws StorageNotFoundException
     * @throws RequestHandlerException
     */
    public function testMiddlewareCanBeLoaded()
    {
        $middleware = $this->getMockClass(MiddlewareInterface::class);
        $mock = $this->getMockBuilder(RequestHandler::class)->disableOriginalConstructor()->getMock();
        $mock->expects($this->exactly(1))->method("load")->with($middleware);
        $app = Application::with()->requestHandler($mock)->create(__DIR__);
        $app->load($middleware);
    }

    public function testGetProviderLoaded()
    {
        $app = new Application(__DIR__);
        $providers = [EventServiceProvider::class, PathProvider::class, DiskManagerProvider::class];
        $this->assertEquals($providers, $app->getProvidersLoaded());
    }

    public function testProvidersCanBeAdded()
    {
        $app = new Application(__DIR__);
        $providers = [EventServiceProvider::class, PathProvider::class, DiskManagerProvider::class];
        $provider1 = $this->createMock(ServiceProviderContract::class);
        $provider2 = $this->getMockBuilder(ServiceProviderContract::class)->getMock();

        $app->providers([$provider1, $provider2]);
        $this->assertEquals(array_merge($providers, [
            get_class($provider1),
            get_class($provider2)
        ]), $app->getProvidersLoaded());
    }
}
