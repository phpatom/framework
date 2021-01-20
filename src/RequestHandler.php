<?php


namespace Atom\Framework;

use Atom\DI\DIC;
use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\Routing\Contracts\RouterContract;
use Atom\Routing\Router;
use Atom\Framework\Contracts\ModuleContract;
use Atom\Framework\Contracts\RendererContract;
use Atom\Framework\Events\AppFailed;
use Atom\Framework\Events\MiddlewareLoaded;
use Atom\Framework\Exceptions\RequestHandlerException;
use Atom\Framework\Middlewares\DispatchRoutes;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandler implements RequestHandlerInterface
{
    /**
     * @var ResponseInterface
     */
    private $response;
    /**
     * @var array<MiddlewareInterface>
     */
    private $middlewareList = [];

    /**
     * @var array<ModuleContract>
     */
    private $moduleList = [];
    /**
     * @var int
     */
    private $index = 0;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var Router
     */
    private $router;
    /**
     * @var ContainerInterface | DIC
     */
    private $container;

    /**
     * @var RendererContract
     */
    private $renderer;

    private $started = false;

    public function __construct(
        ContainerInterface $container,
        EventDispatcherInterface $eventDispatcher,
        RouterContract $router
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->container = $container;
        $this->router = $router;
    }

    /**
     * @param $middleware
     * @return $this
     * @throws RequestHandlerException
     */
    public function add($middleware): self
    {
        $this->validateMiddleware($middleware);
        $this->middlewareList[] = $middleware;
        return $this;
    }

    /**
     * @param $module
     * @return $this
     */
    public function addModule($module): self
    {
        $this->moduleList[] = $module;
        return $this;
    }

    /**
     * @param $middleware
     * @return $this
     * @throws RequestHandlerException
     */
    public function next($middleware): self
    {
        $this->validateMiddleware($middleware);
        $this->pipeAtPosition($this->index + 1, $middleware);
        return $this;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws RequestHandlerException
     * @throws StorageNotFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            if (!$this->started) {
                foreach ($this->moduleList as $module) {
                    /**
                     * @var $instance ModuleContract
                     */
                    $instance = $this->build($module);
                    $instance->bootstrap();
                }
                $this->add(new DispatchRoutes($this->router, $this->container()));
                $this->started = true;
            }

            $currentMiddleware = $this->getCurrentMiddleware();
            $this->index++;
            if (!is_null($currentMiddleware)) {
                $this->eventDispatcher->dispatch(new MiddlewareLoaded($currentMiddleware));
                $this->response = $currentMiddleware->process($request, $this);
            }
            return $this->response;
        } catch (Exception $exception) {
            $this->eventDispatcher->dispatch(new AppFailed($this, $exception, $request));
            throw $exception;
        }
    }

    /**
     * @return MiddlewareInterface |null
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    private function getCurrentMiddleware(): ?MiddlewareInterface
    {
        if (!isset($this->middlewareList[$this->index])) {
            return null;
        }
        return $this->build(
            $this->middlewareList[$this->index]
        );
    }

    /**
     * @param $middleware
     * @return MiddlewareInterface | null
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    private function build($middleware): ?object
    {
        if (is_null($middleware)) {
            return null;
        }
        $instance = $middleware;
        if (is_string($middleware)) {
            $instance = $this->container->get($middleware);
        }
        return $instance;
    }

    /**
     * @return RendererContract
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    public function renderer(): RendererContract
    {
        if (!$this->renderer) {
            $this->renderer = $this->container->get(RendererContract::class);
        }
        return $this->renderer;
    }

    /**
     * @param ResponseInterface $response
     */
    public function emit(ResponseInterface $response): void
    {
        $emitter = new Emitter();
        $emitter->emit($response);
    }

    /**
     * @param $middleware
     * @return RequestHandler
     * @throws RequestHandlerException
     */
    public function load($middleware): self
    {
        $this->pipeReplacement($middleware);
        return $this;
    }

    /**
     * @param $middleware
     * @return RequestHandler
     * @throws RequestHandlerException
     */
    private function pipeReplacement($middleware): self
    {
        $this->validateMiddleware($middleware);
        $this->pipeAtPosition($this->index, $middleware);
        return $this;
    }

    /**
     * @param int $index
     * @param $middleware
     * @return $this
     * @throws RequestHandlerException
     */
    private function pipeAtPosition(int $index, $middleware): self
    {
        if (!$this->isValidIndex($index)) {
            throw new RequestHandlerException("The position [$index] is not valid. 
            It should be either the start, the end or in between the two! ");
        }
        $this->validateMiddleware($middleware);
        array_splice(
            $this->middlewareList,
            $index,
            0,
            is_array($middleware) ? $middleware : [$middleware]
        );
        return $this;
    }

    /**
     * @param $index
     * @return bool
     */
    private function isValidIndex($index): bool
    {
        return ($index >= 0 && $index <= count($this->middlewareList));
    }


    /**
     * @param $middleware
     * @throws RequestHandlerException
     */
    private function validateMiddleware($middleware)
    {
        if (!$this->isValidMiddlewareArg($middleware)) {
            throw new RequestHandlerException("The middleware 
                [{$this->getMiddlewareArgName($middleware)}] is not valid");
        }
    }

    /**
     * @param $arg
     * @return bool
     */
    private function isValidMiddlewareArg($arg): bool
    {
        return is_string($arg) || $arg instanceof MiddlewareInterface || is_array($arg);
    }

    /**
     * @param $middleware
     * @return String
     */
    private function getMiddlewareArgName($middleware): string
    {
        if (!is_object($middleware)) {
            return is_string($middleware) ? ($middleware) : gettype($middleware);
        }
        if (is_array($middleware)) {
            return "array";
        }
        return get_class($middleware);
    }

    /**
     * @return Router
     */
    public function router(): RouterContract
    {
        return $this->router;
    }

    /**
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewareList;
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws RequestHandlerException
     * @throws StorageNotFoundException
     */
    public function run()
    {
        $response = $this->handle(Request::incoming());
        $this->emit($response);
    }

    /**
     * @return ContainerInterface | DIC
     */
    public function container(): ContainerInterface
    {
        return $this->container;
    }
}
