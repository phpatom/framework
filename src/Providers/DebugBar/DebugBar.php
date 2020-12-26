<?php


namespace Atom\Web\Providers\DebugBar;


use Atom\App\App;
use Atom\App\Contracts\ServiceProviderContract;
use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\Web\Exceptions\RequestHandlerException;
use Atom\Web\WebApp;
use InvalidArgumentException;

class DebugBar implements ServiceProviderContract
{
    /**
     * @param App $app
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     * @throws RequestHandlerException
     */
    public function register(App $app)
    {
        if (!($app instanceof WebApp)) {
            throw new InvalidArgumentException("Debug bar can only be use with WebApp");
        }
        $debugBar = $app->container()->get(AtomDebugBar::class);
        $app->container()->singletons()->bindInstance($debugBar);
        $app->requestHandler()->add(DebugBarAssetMiddleware::class);
        $app->requestHandler()->add(DebugBarMiddleware::class);
    }
}