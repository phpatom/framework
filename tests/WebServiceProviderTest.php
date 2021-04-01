<?php


namespace Atom\Framework\Test;

use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\Event\EventDispatcher;
use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;
use Atom\Framework\Application;
use Atom\Framework\Contracts\EmitterContract;
use Atom\Framework\FileSystem\Path;
use Atom\Framework\Http\Emitter\SapiEmitter;
use Atom\Framework\Http\Emitter\SapiStreamEmitter;
use Atom\Framework\Http\RequestHandler;
use Atom\Framework\Kernel;
use Atom\Framework\WebServiceProvider;
use Atom\Routing\Contracts\RouterContract;
use Atom\Routing\Router;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class WebServiceProviderTest extends TestCase
{

    public function testNamedConstructor()
    {
        $this->assertEquals(WebServiceProvider::create(), new WebServiceProvider());
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public function testAllServiceAreProvided()
    {
        $kernel = new Kernel(__DIR__);
        $kernel->use(new WebServiceProvider());
        $this->assertContains(WebServiceProvider::class, $kernel->getRegisteredProviders());
        /**
         * @var Application $app
         */
        $app = $kernel->container()->get(Application::class);
        $this->assertInstanceOf(Application::class, $app);
        $this->assertEquals($app->kernel(), $kernel);

        $this->assertEquals($app->router(), $kernel->get(Router::class));
        $this->assertEquals($app->router(), $kernel->get(RouterContract::class));

        $this->assertEquals($app->requestHandler()->emitter(), $kernel->get(EmitterContract::class));
        $this->assertEquals($app->requestHandler()->emitter(), $kernel->get(SapiEmitter::class));

        $this->assertInstanceOf(RequestHandler::class, $app->requestHandler());
        $this->assertInstanceOf(RequestHandlerInterface::class, $app->requestHandler());
        $this->assertEquals($app->requestHandler(), $kernel->get(RequestHandler::class));
        $this->assertEquals($app->requestHandler(), $kernel->get(RequestHandlerInterface::class));

        //DOTENV LOADED
        $this->assertEquals("FOO", $_ENV["TEST_SECRET"] ?? null);
        $this->assertEquals(__DIR__, $app->path());
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    public function testRequestHandler()
    {
        $kernel = new Kernel(__DIR__);
        $c = $kernel->container();
        $kernel->container()->bindings()->store(
            EventDispatcherInterface::class,
            $c->as()->object(new EventDispatcher())
        );
        $kernel->container()->bindings()->store(
            RouterContract::class,
            $c->as()->object(new Router())
        );

        $requestHandler = new RequestHandler(
            $kernel->container()
        );
        $kernel->use(WebServiceProvider::create()->requestHandler($requestHandler));
        $this->assertEquals($kernel->get(RequestHandler::class), $requestHandler);
        $this->expectException(InvalidArgumentException::class);
        WebServiceProvider::create()->requestHandler(1);
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    public function testEmitter()
    {
        $kernel = new Kernel(__DIR__);
        $emitter = new SapiStreamEmitter();
        $kernel->use(WebServiceProvider::create()->emitter($emitter));
        $this->assertEquals($kernel->get(EmitterContract::class), $emitter);
        $this->expectException(InvalidArgumentException::class);
        WebServiceProvider::create()->emitter(1);
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    public function testRouter()
    {
        $kernel = new Kernel(__DIR__);
        $router = new Router();
        $kernel->use(WebServiceProvider::create()->router($router));
        $this->assertEquals($kernel->get(RouterContract::class), $router);
        $this->expectException(InvalidArgumentException::class);
        WebServiceProvider::create()->router(new SapiEmitter());
    }

    /**
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    public function testPublicPath()
    {
        $kernel = new Kernel(__DIR__);
        $kernel->use(WebServiceProvider::create()->publicPath($path = DIRECTORY_SEPARATOR));
        $this->assertEquals($kernel->get(Path::class)->public("bar"), $path . "bar");
    }

}
