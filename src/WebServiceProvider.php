<?php

namespace Atom\Web;

use Atom\App\App;
use Atom\App\Contracts\ServiceProviderContract;
use Atom\DI\DIC;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\Routing\Contracts\RouterContract;
use Atom\Routing\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WebServiceProvider implements ServiceProviderContract
{

    /**
     * @param App $app
     * @throws StorageNotFoundException
     */
    public function register(App $app)
    {
        $c = $app->container();
        $c->singletons()->store(ContainerInterface::class, $c->as()->object($c));
        $this->provideApp($c, $app);
        $this->provideRequestHandler($c);
        $this->providerRouter($c);
        $this->provideEmitter($c);
    }

    /**
     * @param DIC $c
     * @throws StorageNotFoundException
     */
    private function provideRequestHandler(DIC $c)
    {
        $appAliases = [RequestHandler::class, RequestHandlerInterface::class];
        foreach ($appAliases as $alias) {
            $c->singletons()->store($alias, $c->as()->instanceOf(RequestHandler::class));
        }
    }

    /**
     * @param DIC $c
     * @throws StorageNotFoundException
     */
    private function providerRouter(DIC $c)
    {
        $routerAliases = [RouterContract::class, Router::class];
        $router = new Router();
        foreach ($routerAliases as $alias) {
            $c->singletons()->store($alias, $c->as()->object($router));
        }
    }

    /**
     * @param DIC $c
     * @param App $app
     * @throws StorageNotFoundException
     */
    private function provideApp(DIC $c, App $app)
    {
        $c->singletons()->store(WebApp::class, $c->as()->object($app));
    }

    /**
     * @param DIC $c
     * @throws StorageNotFoundException
     */
    private function provideEmitter(DIC $c)
    {
        $c->singletons()->store(Emitter::class, $c->as()->object($c));
    }
}
