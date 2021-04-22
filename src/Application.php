<?php


namespace Atom\Framework;

use Atom\DI\Container;
use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\Event\Contracts\EventDispatcherContract;
use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;
use Atom\Framework\Contracts\EmitterContract;
use Atom\Framework\Contracts\HasKernel;
use Atom\Framework\Contracts\ServiceProviderContract;
use Atom\Framework\FileSystem\Path;
use Atom\Framework\Http\RequestHandler;
use Atom\Routing\CanRegisterRoute;
use Atom\Routing\Route;
use Atom\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionException;
use Throwable;

class Application
{
    /**
     * @var Kernel
     */
    private Kernel $kernel;

    /**
     * @var RequestHandler|null
     */
    private ?RequestHandler $requestHandler = null;

    /**
     * @var Router|null
     */
    private ?Router $router = null;

    /**
     * @var Path|null
     */
    private ?Path $path = null;

    /**
     * Application constructor.
     * @param HasKernel $hasKernel
     * @param WebServiceProvider|null $webServiceProvider
     * @param bool $preventDefaultProvider
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public function __construct(
        HasKernel $hasKernel,
        ?WebServiceProvider $webServiceProvider = null,
        bool $preventDefaultProvider = false
    ) {
        $this->kernel = $hasKernel->getKernel();
        if ((!$preventDefaultProvider) || ($webServiceProvider != null)) {
            $this->kernel->use($webServiceProvider ?? new WebServiceProvider());
        }
    }

    use CanRegisterRoute {
        create as public route;
    }

    /**
     * @param string $appDir
     * @param string|null $env
     * @return Application
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public static function create(
        string $appDir,
        string $env = Env::DEV
    ): self {
        return new self(new Kernel($appDir, $env), new WebServiceProvider());
    }

    /**
     * @return ApplicationFactory
     */
    public static function with(): ApplicationFactory
    {
        return new ApplicationFactory();
    }

    /**
     * @param HasKernel $kernel
     * @return static
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public static function of(HasKernel $kernel): self
    {
        return new Application($kernel, null, true);
    }

    /**
     * @param string $appDir
     * @return Application
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public static function prod(string $appDir): Application
    {
        return self::create($appDir, Env::PRODUCTION);
    }

    /**
     * @param string $appDir
     * @return Application
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public static function dev(string $appDir): Application
    {
        return self::create($appDir);
    }

    /**
     * @param string $appDir
     * @return Application
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public static function test(string $appDir): Application
    {
        return self::create($appDir, Env::TESTING);
    }

    /**
     * @param string $appDir
     * @return Application
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public static function staging(string $appDir): Application
    {
        return self::create($appDir, Env::STAGING);
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws Exceptions\RequestHandlerException
     * @throws NotFoundException
     * @throws Throwable
     */
    public function run()
    {
        $this->kernel()->boot();
        $this->requestHandler()->run();
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws Exceptions\RequestHandlerException
     * @throws NotFoundException
     * @throws Throwable
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->kernel()->boot();
        return $this->requestHandler()->handle($request);
    }

    /**
     * @param ResponseInterface $response
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function emit(ResponseInterface $response)
    {
        $this->requestHandler()->emit($response);
    }

    public function container(): ?Container
    {
        return $this->kernel->container();
    }

    public function eventDispatcher(): ?EventDispatcherContract
    {
        return $this->kernel->eventDispatcher();
    }

    /**
     * @return mixed|void
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function requestHandler(): RequestHandler
    {
        if ($this->requestHandler == null) {
            $this->requestHandler = $this->container()->get(RequestHandler::class);
        }
        return $this->requestHandler;
    }

    /**
     * @return EmitterContract
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException v
     * @throws ReflectionException
     */
    public function emitter(): EmitterContract
    {
        return $this->requestHandler()->emitter();
    }

    /**
     * @return Path
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException v
     * @throws ReflectionException
     */
    public function path(): Path
    {
        if ($this->path == null) {
            $this->path = $this->container()->get(Path::class);
        }
        return $this->path;
    }

    /**
     * @return Router
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException v
     * @throws ReflectionException
     */
    public function router(): Router
    {
        if ($this->router == null) {
            $this->router = $this->container()->get(Router::class);
        }
        return $this->router;
    }


    /**
     * @param string $prefix
     * @param callable $callable
     * @param null $handler
     * @return Application
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException v
     * @throws ReflectionException
     */
    public function group(string $prefix, callable $callable, $handler = null): self
    {
        $this->router()->group($prefix, $callable, $handler);
        return $this;
    }


    /**
     * @param string|MiddlewareInterface $middleware
     * @return Application
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws Exceptions\RequestHandlerException
     * @throws NotFoundException v
     * @throws ReflectionException
     */
    public function add($middleware): Application
    {
        $this->requestHandler()->pipe($middleware);
        return $this;
    }

    /**
     * @param $middleware
     * @return Application
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws Exceptions\RequestHandlerException
     * @throws NotFoundException v
     * @throws ReflectionException
     */
    public function load($middleware): Application
    {
        $this->requestHandler()->load($middleware);
        return $this;
    }

    /**
     * @return Kernel
     */
    public function kernel(): Kernel
    {
        return $this->kernel;
    }

    /**
     * @return array
     */
    public function getRegisteredProviders(): array
    {
        return $this->kernel()->getRegisteredProviders();
    }

    /**
     * @param Route $route
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException v
     * @throws ReflectionException
     */
    protected function registerRoute(Route $route)
    {
        $this->router()->add($route);
    }

    public function env(): Env
    {
        return $this->kernel()->env();
    }

    /**
     * @param ServiceProviderContract $serviceProvider
     * @return $this
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public function use(ServiceProviderContract $serviceProvider): self
    {
        $this->kernel()->use($serviceProvider);
        return $this;
    }

    /**
     * @param array $providers
     * @return $this
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public function providers(array $providers): self
    {
        $this->kernel()->providers($providers);
        return $this;
    }
}
