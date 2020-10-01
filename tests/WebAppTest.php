<?php


namespace Atom\Web\Tests;

use Atom\App\App;
use Atom\Routing\Contracts\RouterContract;
use Atom\Web\WebApp;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class WebAppTest extends TestCase
{
    public function testItCanBeCreated()
    {
        $app = WebApp::create(__DIR__);
        $this->assertInstanceOf(App::class, $app);
    }

    public function testItProvidesWebServices()
    {
        $app = WebApp::create(__DIR__);
        $this->assertInstanceOf(RouterContract::class, $router = $app->router());
        $this->assertEquals($router, $app->router());

        $this->assertInstanceOf(RequestHandlerInterface::class, $handler = $app->requestHandler());
        $this->assertEquals($handler, $app->requestHandler());
    }
}
