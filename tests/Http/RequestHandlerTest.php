<?php


namespace Atom\Framework\Test\Http;

use Atom\Framework\Http\RequestHandler;
use Atom\Framework\Kernel;
use Atom\Routing\Contracts\RouterContract;
use Atom\Routing\Router;
use PHPUnit\Framework\TestCase;

class RequestHandlerTest extends TestCase
{
    public function makehandler(?RouterContract $router = null): RequestHandler
    {
        $kernel = new Kernel("foo");
        $container = $kernel->container();
        $container->bind(RouterContract::class, $router ?? new Router());
        return new RequestHandler($container);
    }

    public function testRouter()
    {
        $router = new Router();
        $this->assertEquals(
            $router,
            $this->makehandler($router)->router()
        );
    }

    public function testKernel()
    {
        $kernel = new Kernel("foo");
        $kernel->container()->bind(RouterContract::class, new Router());
        $kernel->container()->bind(Kernel::class, $kernel);

        $handler = new RequestHandler($kernel->container());
        $this->assertEquals(
            $kernel,
            $handler->getKernel()
        );
    }

    public function testGetMiddlewares()
    {

    }
}
