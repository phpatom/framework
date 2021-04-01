<?php


namespace Atom\Framework\Test;

use Atom\DI\Container;
use Atom\Event\AbstractEvent;
use Atom\Event\AbstractEventListener;
use Atom\Event\EventDispatcher;
use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;
use Atom\Framework\Application;
use Atom\Framework\ApplicationFactory;
use Atom\Framework\Contracts\ServiceProviderContract;
use Atom\Framework\Env;
use Atom\Framework\Http\Emitter\SapiEmitter;
use Atom\Framework\Http\RequestHandler;
use Atom\Framework\Kernel;
use Atom\Framework\WebServiceProvider;
use Atom\Routing\Contracts\RouterContract;
use Atom\Routing\Router;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;

class ApplicationFactoryTest extends TestCase
{
    public function testNamedConstructors()
    {
        $factory = ApplicationFactory::with();
        $this->assertInstanceOf(ApplicationFactory::class, $factory);
        $factory = ApplicationFactory::new();
        $this->assertInstanceOf(ApplicationFactory::class, $factory);
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public function testCreate()
    {
        $app = ApplicationFactory::new()->create(__DIR__, Env::STAGING);
        $this->assertInstanceOf(Application::class, $app);
        $this->assertTrue($app->env()->isStaging());
        $this->assertContains(WebServiceProvider::class, $app->getRegisteredProviders());
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public function testContainer()
    {
        $container = new Container();
        $container->bind("foo")->toValue("bar");
        $app = ApplicationFactory::with()->container($container)->create(__DIR__);
        $this->assertEquals($container, $app->container());
        $this->assertEquals("bar", $app->container()->get("foo"));
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public function testEventDispatcher()
    {
        $eventDispatcher = new EventDispatcher();
        $event = new class extends AbstractEvent {
        };
        $myListener = new class extends AbstractEventListener {
            public function on($event): void
            {
            }

            public function called(): bool
            {
                return $this->getCalls() > 0;
            }

        };
        $eventDispatcher->addEventListener(get_class($event), $myListener);
        $app = ApplicationFactory::with()->eventDispatcher($eventDispatcher)->create(__DIR__);
        $this->assertEquals($app->eventDispatcher(), $eventDispatcher);
        $app->eventDispatcher()->dispatch($event);
        $this->assertTrue($myListener->called());
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public function testRouter()
    {
        $router = new Router();
        $app = ApplicationFactory::with()->router($router)->create(__DIR__);
        $this->assertEquals($router, $app->router());
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public function testRequestHandler()
    {
        $c = new Container();
        $c->bind(EventDispatcherInterface::class)->toValue(new EventDispatcher());
        $c->bind(RouterContract::class)->toObject(new Router());
        $requestHandler = new RequestHandler($c);
        $app = ApplicationFactory::with()->requestHandler($requestHandler)->create(__DIR__);
        $this->assertEquals($requestHandler, $app->requestHandler());
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public function testEmitter()
    {
        $emitter = new SapiEmitter();
        $app = ApplicationFactory::with()->emitter($emitter)->create(__DIR__);
        $this->assertEquals($emitter, $app->emitter());
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public function testKernel()
    {
        $kernel = new Kernel("foo", Env::PRODUCTION);
        $container = new Container();
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addEventListener("foo", new class extends AbstractEventListener {
            public function on($event): void
            {
            }
        });
        $app = ApplicationFactory::with()
            ->container($container)
            ->eventDispatcher($eventDispatcher)
            ->kernel($kernel)
            ->create();
        $this->assertEquals($kernel, $app->kernel());
        $this->assertTrue($app->env()->isProduction());
        $this->assertEquals("foo", $kernel->appPath());

        $this->assertNotEquals($container, $app->kernel()->container());
        $this->assertNotEquals($eventDispatcher, $app->kernel()->eventDispatcher());
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public function testIn()
    {
        $app = ApplicationFactory::with()->env(Env::PRODUCTION)->create("foo");
        $this->assertTrue($app->env()->isProduction());
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public function modules()
    {
        $provider = new class implements ServiceProviderContract {

            public function register(Kernel $kernel)
            {
                $c = $kernel->container();
                $c->bind("foo")->toValue("bar");
            }
        };

        $app = ApplicationFactory::with()->providers([
            $provider
        ])->create(__DIR__);
        $this->assertContains(get_class($provider), $app->getRegisteredProviders());
        $this->assertEquals("bar", $app->container()->get("foo"));
    }
}
