<?php

namespace Atom\Web;

use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;
use Atom\Kernel\Contracts\ServiceProviderContract;
use Atom\DI\DIC;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\Kernel\Env\Env;
use Atom\Kernel\Kernel;
use Atom\Routing\Contracts\RouterContract;
use Atom\Routing\Router;
use Atom\Web\Contracts\EmitterContract;
use Atom\Web\Contracts\RendererContract;
use Atom\Web\Http\Emitter\SapiEmitter;
use Atom\Web\Http\RequestHandler;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WebServiceProvider implements ServiceProviderContract
{
    /**
     * @var string|RequestHandler
     */
    private $requestHandler = RequestHandler::class;

    /**
     * @var string|Router
     */
    private $router = Router::class;

    private $loadDotEnv = false;

    /**
     * @var string|EmitterContract
     */
    private $emitter = SapiEmitter::class;

    /**
     * @var DIC $container
     */
    private $container;


    /**
     * @param Kernel $app
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    public function register(Kernel $app)
    {
        $c = $app->container();
        $this->container = $c;
        $c->singletons()->store(ContainerInterface::class, $c->as()->object($c));
        if ($this->loadDotEnv) {
            $app->env()->dotEnv()->safeLoad();
        }
        $this->provideApp($c, $app);
        $this->providerRouting($c);
        $this->provideEmitter($c);
        $this->provideRequestHandler($c);
    }

    /**
     * @param DIC $c
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    private function provideRequestHandler(DIC $c)
    {
        $requestHandler = $this->makeRequestHandler();
        $requestHandlerClassName = get_class($requestHandler);
        $aliases = [RequestHandler::class, RequestHandlerInterface::class];
        if ($requestHandlerClassName !== RequestHandler::class) {
            $aliases[] = $requestHandlerClassName;
        }
        foreach ($aliases as $alias) {
            $c->singletons()->store($alias, $c->as()->object($requestHandler));
        }
    }

    /**
     * @param DIC $c
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    private function providerRouting(DIC $c)
    {
        $routerAliases = [RouterContract::class, Router::class];
        $router = $this->makeRouter();
        if (get_class($router) !== Router::class) {
            $routerAliases[] = get_class($router);
        }
        foreach ($routerAliases as $alias) {
            $c->singletons()->store($alias, $c->as()->object($router));
        }
        //Extension
        $c->resolved(RendererContract::class, function (RendererContract $renderer) use ($router) {
            $renderer->addExtensions(new RoutingExtensionProvider($router));
        });
    }

    /**
     * @param DIC $c
     * @param $kernel
     * @throws StorageNotFoundException
     */
    private function provideApp(DIC $c, $kernel)
    {
        $c->singletons()->bindInstance($kernel);
    }

    /**
     * @param DIC $c
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    private function provideEmitter(DIC $c)
    {
        $emitter = $this->makeEmitter();
        $aliases = [EmitterContract::class, get_class($emitter)];
        foreach ($aliases as $alias) {
            $c->singletons()->store($alias, $c->as()->object($emitter));
        }
    }

    /**
     * @param string|RequestHandler $requestHandler
     */
    public function requestHandler($requestHandler): self
    {
        if (!is_string($requestHandler) && !($requestHandler instanceof RequestHandler)) {
            throw new InvalidArgumentException(
                "The request handler should be either a classname or an object that extends "
                . RequestHandler::class
            );
        }
        $this->requestHandler = $requestHandler;
        return $this;
    }

    /**
     * @param string $emitter
     * @return WebServiceProvider
     */
    public function emitter(string $emitter): self
    {
        if (!is_string($emitter) && !($emitter instanceof EmitterContract)) {
            throw new InvalidArgumentException(
                "The router should be either a classname or an object that implements "
                . EmitterContract::class
            );
        }
        $this->emitter = $emitter;
        return $this;
    }

    /**
     * @param string|Router $router
     * @return WebServiceProvider
     */
    public function router($router): self
    {
        if (!is_string($router) && !($router instanceof Router)) {
            throw new InvalidArgumentException(
                "The router should be either a classname or an object that extends "
                . Router::class
            );
        }
        $this->router = $router;
        return $this;
    }

    /**
     * @return Router
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    private function makeRouter(): Router
    {
        if (is_string($this->router)) {
            return $this->container->get($this->router);
        }
        return $this->router;
    }

    /**
     * @return EmitterContract
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    private function makeEmitter(): EmitterContract
    {
        if (is_string($this->emitter)) {
            return $this->container->get($this->emitter);
        }
        return $this->emitter;
    }

    /**
     * @return RequestHandler
     * @throws StorageNotFoundException
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     */
    private function makeRequestHandler(): RequestHandler
    {
        if (is_string($this->requestHandler)) {
            return $this->container->get($this->requestHandler);
        }
        return $this->requestHandler;
    }

    /**
     * @return $this
     */
    public function withoutDotEnv(): self
    {
        $this->loadDotEnv = false;
        return $this;
    }

    /**
     * @return $this
     */
    public function dotEnv(): self
    {
        $this->loadDotEnv = true;
        return $this;
    }

    /**
     * @param string $appPath
     * @param string $env
     * @return Application
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     * @throws ListenerAlreadyAttachedToEvent
     */
    public function create(string $appPath, string $env = Env::DEV): Application
    {
        return (new Application($appPath, $env))->use($this);
    }
}
