<?php


namespace Atom\Framework;

use Atom\DI\Container;
use Atom\DI\Exceptions\MultipleBindingException;
use Atom\Event\Contracts\EventDispatcherContract;
use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;
use Atom\Framework\Contracts\EmitterContract;
use Atom\Framework\Contracts\ServiceProviderContract;
use Atom\Framework\Http\RequestHandler;
use Atom\Routing\Router;
use Throwable;

/**
 * Class ApplicationFactory
 * @package Atom\Framework
 */
class ApplicationFactory
{
    private ?Container $container = null;
    private ?EventDispatcherContract $eventDispatcher = null;
    private ?Router $router = null;
    private ?RequestHandler $requestHandler = null;
    private ?EmitterContract $emitter = null;
    private ?Kernel $kernel = null;
    private ?string $env = null;
    /**
     * @var array<ServiceProviderContract>
     */
    private array $providers = [];

    public static function with(): ApplicationFactory
    {
        return self::new();
    }

    public static function new(): ApplicationFactory
    {
        return new ApplicationFactory();
    }

    /**
     * @param string|null $appPath
     * @param string|null $env
     * @return Application
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    public function create(?string $appPath = null, ?string $env = null): Application
    {
        $kernel = $this->makeKernel($appPath, $env);
        $serviceProvider = $this->makeServiceProvider();
        $app = new Application($kernel, $serviceProvider);
        $app->providers($this->providers);
        return $app;
    }


    /**
     * @param Container|null $container
     * @return ApplicationFactory
     */
    public function container(?Container $container): ApplicationFactory
    {
        $this->container = $container;
        return $this;
    }

    /**
     * @param EventDispatcherContract|null $eventDispatcher
     * @return ApplicationFactory
     */
    public function eventDispatcher(?EventDispatcherContract $eventDispatcher): ApplicationFactory
    {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }

    /**
     * @param Router|null $router
     * @return ApplicationFactory
     */
    public function router(?Router $router): ApplicationFactory
    {
        $this->router = $router;
        return $this;
    }

    /**
     * @param RequestHandler|null $requestHandler
     * @return ApplicationFactory
     */
    public function requestHandler(?RequestHandler $requestHandler): ApplicationFactory
    {
        $this->requestHandler = $requestHandler;
        return $this;
    }

    /**
     * @param EmitterContract|null $emitter
     * @return ApplicationFactory
     */
    public function emitter(?EmitterContract $emitter): ApplicationFactory
    {
        $this->emitter = $emitter;
        return $this;
    }

    /**
     * @param Kernel|null $kernel
     * @return ApplicationFactory
     */
    public function kernel(?Kernel $kernel): ApplicationFactory
    {
        $this->kernel = $kernel;
        return $this;
    }

    /**
     * @param string|null $env
     * @return ApplicationFactory
     */
    public function in(?string $env): self
    {
        $this->env = $env;
        return $this;
    }

    /**
     * @param string|null $env
     * @return ApplicationFactory
     */
    public function env(?string $env): self
    {
        return $this->in($env);
    }

    /**
     * @param ServiceProviderContract[] $providers
     * @return ApplicationFactory
     */
    public function providers(array $providers): ApplicationFactory
    {
        $this->providers = $providers;
        return $this;
    }

    private function getEnv(?string $env): string
    {
        return $env ?? $this->env ?? Env::DEV;
    }

    /**
     * @param string|null $appPath
     * @param string|null $env
     * @return Kernel
     * @throws MultipleBindingException
     */
    private function makeKernel(?string $appPath = null, ?string $env = null): Kernel
    {
        if ($this->kernel) {
            return $this->kernel;
        }
        return new Kernel($appPath, $this->getEnv($env), $this->container, $this->eventDispatcher);
    }

    private function makeServiceProvider(): WebServiceProvider
    {
        return WebServiceProvider::create()
            ->requestHandler($this->requestHandler)
            ->router($this->router)
            ->emitter($this->emitter);
    }
}
