<?php

namespace Atom\Web;

use Atom\Kernel\Contracts\ServiceProviderContract;
use Atom\DI\DIC;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\Kernel\Kernel;
use Atom\Routing\Contracts\RouterContract;
use Atom\Routing\Router;
use Atom\Web\Contracts\RendererContract;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WebServiceProvider implements ServiceProviderContract
{

    /**
     * @param Kernel $app
     * @throws StorageNotFoundException
     */
    public function register(Kernel $app)
    {
        $c = $app->container();
        $app->env()->dotEnv()->safeLoad();
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
        $c->resolved(RendererContract::class, function (RendererContract $renderer) use ($router) {
            $renderer->addExtensions(new RoutingExtensionProvider($router));
        });
    }

    /**
     * @param DIC $c
     * @param Kernel $kernel
     * @throws StorageNotFoundException
     */
    private function provideApp(DIC $c, Kernel $kernel)
    {
        $c->singletons()->store(Application::class, $c->as()->object($kernel));
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
