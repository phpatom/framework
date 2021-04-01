<?php

namespace Atom\Framework;

use Atom\DI\Container;
use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\MultipleBindingException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;
use Atom\Framework\Contracts\EmitterContract;
use Atom\Framework\Contracts\RendererContract;
use Atom\Framework\Contracts\ServiceProviderContract;
use Atom\Framework\FileSystem\Path;
use Atom\Framework\Http\Emitter\SapiEmitter;
use Atom\Framework\Http\RequestHandler;
use Atom\Framework\Http\ResponseSender;
use Atom\Framework\Rendering\RoutingExtensionProvider;
use Atom\Routing\Contracts\RouterContract;
use Atom\Routing\Router;
use InvalidArgumentException;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;
use Throwable;

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

    /**
     * @var string|EmitterContract
     */
    private $emitter = SapiEmitter::class;

    /**
     * @var Container $container
     */
    private Container $container;

    private ?string $publicPath = null;

    public static function create(): self
    {
        return new self();
    }

    /**
     * @param Kernel $kernel
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws Throwable
     */
    public function register(Kernel $kernel)
    {
        $c = $kernel->container();
        $this->container = $c;
        $this->provideDotEnv($kernel);
        $this->providePath($kernel);
        $this->provideApp($c, $kernel);
        $this->providerRouting($c);
        $this->provideEmitter($c);
        $this->provideRequestHandler($c);
    }


    /**
     * @param string|RequestHandler $requestHandler
     * @return WebServiceProvider
     */
    public function requestHandler($requestHandler): self
    {
        if (is_null($requestHandler)) {
            return $this;
        }
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
     * @param string|null|EmitterContract $emitter
     * @return WebServiceProvider
     */
    public function emitter($emitter = null): self
    {
        if (is_null($emitter)) {
            return $this;
        }
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
     * @param string|null $publicPath
     * @return WebServiceProvider
     */
    public function publicPath(?string $publicPath): WebServiceProvider
    {
        $this->publicPath = $publicPath;
        return $this;
    }


    /**
     * @param string|Router|null $router
     * @return WebServiceProvider
     */
    public function router($router): self
    {
        if (is_null($router)) {
            return $this;
        }
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
     * @param Kernel $kernel
     * @throws Exceptions\AppAlreadyBootedException
     */
    private function provideDotEnv(Kernel $kernel)
    {
        $kernel->env()->dotEnv()->safeLoad();
        $kernel->setMetaData("app.web.env", $kernel->env());
    }

    /**
     * @param Kernel $kernel
     * @throws Exceptions\AppAlreadyBootedException
     */
    private function providePath(Kernel $kernel)
    {
        $path = new Path($kernel->appPath(), $this->publicPath);
        $kernel->container()->bindIfNotAvailable(Path::class)->toObject($path);
        $kernel->setMetaData("app.web.path", $path);
    }

    /**
     * @param Container $c
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    private function provideRequestHandler(Container $c)
    {
        $requestHandler = $this->makeRequestHandler();
        $requestHandlerClassName = get_class($requestHandler);
        $aliases = [RequestHandler::class, RequestHandlerInterface::class];
        if ($requestHandlerClassName !== RequestHandler::class) {
            $aliases[] = $requestHandlerClassName;
        }
        $c->bindIfNotAvailable($aliases)->toObject($requestHandler);
    }

    /**
     * @param Container $c
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    private function providerRouting(Container $c)
    {
        $routerAliases = [RouterContract::class, Router::class];
        $router = $this->makeRouter();
        if (get_class($router) !== Router::class) {
            $routerAliases[] = get_class($router);
        }
        $this->container->bindIfNotAvailable($routerAliases, $router);
        $c->bindIfNotAvailable(ResponseSender::class, new ResponseSender(
            $router
        ));
        //Extension
        $c->resolved(RendererContract::class, function (RendererContract $renderer) use ($router) {
            $renderer->addExtensions(new RoutingExtensionProvider($router));
        });
    }

    /**
     * @param Container $c
     * @param $kernel
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    private function provideApp(Container $c, $kernel)
    {
        $c->bindIfNotAvailable(Kernel::class)->toObject($kernel);
        $c->bindIfNotAvailable(Application::class)->toObject(Application::of($kernel));
    }

    /**
     * @param Container $c
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    private function provideEmitter(Container $c)
    {
        $emitter = $this->makeEmitter();
        $c->bindIfNotAvailable([EmitterContract::class, get_class($emitter)])->toObject($emitter);
    }

    /**
     * @return Router
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
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
     * @throws ReflectionException
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
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    private function makeRequestHandler(): RequestHandler
    {
        if (is_string($this->requestHandler)) {
            return $this->container->get($this->requestHandler);
        }
        return $this->requestHandler;
    }
}
