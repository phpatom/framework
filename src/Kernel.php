<?php

namespace Atom\Framework;

use Atom\DI\Container;
use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\MultipleBindingException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\Event\Contracts\EventDispatcherContract;
use Atom\Event\EventDispatcher;
use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;
use Atom\Framework\Contracts\HasKernel;
use Atom\Framework\Contracts\ServiceProviderContract;
use Atom\Framework\Events\ServiceProviderRegistered;
use Atom\Framework\Events\ServiceProviderRegistrationFailed;
use Atom\Framework\Exceptions\AppAlreadyBootedException;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionException;
use Throwable;

class Kernel implements HasKernel
{
    private bool $booted = false;
    /**
     * @var array<string>
     */
    private array $registeredProviders = [];
    /**
     * @var array<string,mixed>
     */
    private array $meta = [];

    /**
     * @var Env
     */
    private Env $env;

    private string $appPath;
    /**
     * @var Container
     */
    private Container $container;
    /**
     * @var EventDispatcherContract
     */
    private EventDispatcherContract $eventDispatcher;

    /**
     * Application constructor.
     * @param string $appPath
     * @param string|null $env
     * @param Container|null $container
     * @param EventDispatcherContract|null $eventDispatcher
     * @throws MultipleBindingException
     */
    public function __construct(
        string $appPath,
        ?string $env = Env::DEV,
        ?Container $container = null,
        ?EventDispatcherContract $eventDispatcher = null
    ) {
        $this->appPath = $appPath;
        $this->env = new Env($this->appPath, $env);
        $this->container = $container ?? new Container();
        $this->eventDispatcher = $eventDispatcher ?? new EventDispatcher();
        $this->providesUtils();
    }

    /**
     * @return Env
     */
    public function env(): Env
    {
        return $this->env;
    }

    /**
     * @param string $alias
     * @return mixed|void
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function get(string $alias)
    {
        return $this->container()->get($alias);
    }

    /**
     * @return string
     */
    public function appPath(): string
    {
        return $this->appPath;
    }

    /**
     * @return Container
     */
    public function container(): Container
    {
        return $this->container;
    }

    /**
     * @return EventDispatcherContract
     */
    public function eventDispatcher(): EventDispatcherContract
    {
        return $this->eventDispatcher;
    }

    /**
     * @param ServiceProviderContract $serviceProvider
     * @return $this
     * @throws Throwable
     * @throws ListenerAlreadyAttachedToEvent
     */
    public function use(ServiceProviderContract $serviceProvider): Kernel
    {
        $this->ensureNotBooted("SERVICE_PROVIDER_REGISTRATION");
        $providerClassName = get_class($serviceProvider);
        if ($this->providerRegistered($providerClassName)) {
            return $this;
        }
        try {
            $serviceProvider->register($this);
            $this->registeredProviders[] = $providerClassName;
            $this->eventDispatcher()->dispatch(new ServiceProviderRegistered($providerClassName));
        } catch (Throwable $exception) {
            $this->eventDispatcher->dispatch(new ServiceProviderRegistrationFailed($serviceProvider, $exception));
            throw $exception;
        }
        return $this;
    }

    /**
     * @param string $providerClassName
     * @return bool
     */
    public function providerRegistered(string $providerClassName): bool
    {
        return in_array($providerClassName, $this->registeredProviders);
    }

    /**
     * @param array $providers
     * @return $this
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public function providers(array $providers): Kernel
    {
        foreach ($providers as $provider) {
            $this->use($provider);
        }
        return $this;
    }

    /**
     * @throws Throwable
     */
    public function boot(): self
    {
        $this->ensureNotBooted("BOOT");
        $this->booted = true;
        return $this;
    }

    /**
     * @param string $key
     * @param $value
     * @return Kernel
     * @throws AppAlreadyBootedException
     */
    public function setMetaData(string $key, $value): Kernel
    {
        $this->ensureNotBooted("SET_META_DATA");
        $this->meta[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasMetaData(string $key): bool
    {
        return array_key_exists($key, $this->meta);
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public function getMetaData(string $key, $default = null)
    {
        return $this->meta[$key] ?? $default;
    }

    public function getKernel(): Kernel
    {
        return $this;
    }

    /**
     * @return string[]
     */
    public function getRegisteredProviders(): array
    {
        return $this->registeredProviders;
    }

    /**
     * @param string $operationName
     * @throws AppAlreadyBootedException
     */
    private function ensureNotBooted(string $operationName)
    {
        if ($this->booted) {
            throw new AppAlreadyBootedException("
            The operation [$operationName] is not allowed when the kernel has already booted");
        }
    }

    /**
     * @throws MultipleBindingException
     */
    private function providesUtils()
    {
        $this->provideContainer();
        $this->provideEventDispatcher();
        $this->provideEnv();
    }

    /**
     * @throws MultipleBindingException
     */
    private function provideContainer()
    {
        $containerAliases = [ContainerInterface::class, get_class($this->container)];
        $this->container->bind($containerAliases)->toObject($this->container);
    }

    /**
     * @throws MultipleBindingException
     */
    private function provideEventDispatcher()
    {
        $this->container->bind([
            EventDispatcherContract::class,
            EventDispatcherInterface::class,
            get_class($this->eventDispatcher)
        ])->toObject($this->eventDispatcher);
    }

    /**
     * @throws MultipleBindingException
     */
    private function provideEnv()
    {
        $this->container->bind(Env::class)->toObject($this->env);
    }
}
