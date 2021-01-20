<?php


namespace Atom\Web\Tests;

use Atom\Kernel\Kernel;
use Atom\Routing\Contracts\RouterContract;
use Atom\Web\Application;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class WebAppTest extends TestCase
{
    public function testItCanBeCreated()
    {
        $app = Application::create(__DIR__);
        $this->assertInstanceOf(App::class, $app);
    }

    public function testItProvidesWebServices()
    {
        $app = Application::create(__DIR__);
        $this->assertInstanceOf(RouterContract::class, $router = $app->router());
        $this->assertEquals($router, $app->router());

        $this->assertInstanceOf(RequestHandlerInterface::class, $handler = $app->requestHandler());
        $this->assertEquals($handler, $app->requestHandler());
    }
}
