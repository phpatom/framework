<?php


namespace Atom\Framework\Http;

use Atom\DI\Container;
use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\MultipleBindingException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\Framework\Contracts\EmitterContract;
use Atom\Framework\Contracts\HasKernel;
use Atom\Framework\Contracts\RendererContract;
use Atom\Framework\Events\AppFailed;
use Atom\Framework\Events\MiddlewareLoaded;
use Atom\Framework\Exceptions\RequestHandlerException;
use Atom\Framework\Http\Middlewares\DispatchRoutes;
use Atom\Framework\Http\Middlewares\FunctionCallback;
use Atom\Framework\Http\Middlewares\MethodCallback;
use Atom\Framework\Kernel;
use Atom\Routing\Contracts\RouterContract;
use Atom\Routing\Router;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionException;

class RequestHandler implements RequestHandlerInterface, HasKernel
{
    /**
     * @var ResponseInterface
     */
    private ResponseInterface $response;
    /**
     * @var array<MiddlewareInterface>
     */
    private array $pipeline = [];

    /**
     * @var array<string>
     */
    private array $registered = [];

    /**
     * @var int
     */
    private int $index = 0;

    /**
     * @var EventDispatcherInterface
     */
    private EventDispatcherInterface $eventDispatcher;
    /**
     * @var Router
     */
    private $router;
    /**
     * @var ContainerInterface | Container
     */
    private $container;

    /**
     * @var RendererContract
     */
    private RendererContract $renderer;

    private bool $started = false;

    /**
     * RequestHandler constructor.
     * @param ContainerInterface $kernel
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function __construct(
        ContainerInterface $kernel
    )
    {
        $this->container = $kernel;
        $this->eventDispatcher = $this->container()->get(EventDispatcherInterface::class);
        $this->router = $this->container()->get(RouterContract::class);
    }

    /**
     * @return Router
     */
    public function router(): RouterContract
    {
        return $this->router;
    }

    /**
     * @return Kernel
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function getKernel(): Kernel
    {
        return $this->container()->get(Kernel::class);
    }

    /**
     * @return ContainerInterface | Container
     */
    public function container(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return array
     */
    public function getRegistered(): array
    {
        return $this->registered;
    }

    /**
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     * @throws Exception
     */
    public function run()
    {
        $response = $this->handle(Request::incoming());
        $this->emit($response);
    }


    /**
     * @param $middleware
     * @return $this
     * @throws RequestHandlerException
     */
    public function pipe($middleware): self
    {
        $this->validateMiddleware($middleware);
        $this->pipeline[] = $middleware;
        $this->registered[] = $this->getMiddlewareArgName($middleware);
        return $this;
    }

    /**
     * @param $middleware
     * @return $this
     * @throws RequestHandlerException
     */
    public function pipeNext($middleware): self
    {
        $this->validateMiddleware($middleware);
        $this->pipeAtPosition($this->index + 1, $middleware);
        return $this;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->ensureStarted($request);
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
     * @param ServerRequestInterface $request
     * @throws MultipleBindingException
     * @throws RequestHandlerException
     */
    private function ensureStarted(ServerRequestInterface $request)
    {
        if ($this->started) {
            return;
        }
        $this->container()->bind([ServerRequestInterface::class, get_class($request)])->toObject($request);
        $this->pipe(new DispatchRoutes($this->router, $this->container()));
        $this->started = true;
    }

    /**
     * @return MiddlewareInterface |null
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    private function getCurrentMiddleware(): ?MiddlewareInterface
    {
        if (!isset($this->pipeline[$this->index])) {
            return null;
        }
        return $this->build(
            $this->pipeline[$this->index]
        );
    }

    /**
     * @param $middleware
     * @return MiddlewareInterface | null
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
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
        if (is_callable($middleware) && !is_array($middleware)) {
            $instance = new FunctionCallback($middleware);
        }
        if (is_callable($middleware) && is_array($middleware)) {
            $instance = new MethodCallback($middleware[0], $middleware[1]);
        }
        return $instance;
    }

    /**
     * @return RendererContract
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
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
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function emit(ResponseInterface $response): void
    {
        /**
         * @var EmitterContract $emitter
         */
        $emitter = $this->container->get(EmitterContract::class);
        $emitter->emit($response);
    }

    /**
     * @return EmitterContract
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function emitter(): EmitterContract
    {
        return $this->container->get(EmitterContract::class);
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
     * @return ResponseSender
     */
    public function sender(): ResponseSender
    {
        return new ResponseSender($this->router());
    }

    /**
     * @param $data
     * @param int $statusCode
     * @return ResponseInterface
     */
    public function send($data, int $statusCode = 200): ResponseInterface
    {
        return $this->sender()->send($data, $statusCode);
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
            $this->pipeline,
            $index,
            0,
            is_array($middleware) ? $middleware : [$middleware]
        );
        $this->registered[] = $this->getMiddlewareArgName($middleware);
        return $this;
    }

    /**
     * @param $index
     * @return bool
     */
    private function isValidIndex($index): bool
    {
        return ($index >= 0 && $index <= count($this->pipeline));
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
        return is_string($arg)
            || $arg instanceof MiddlewareInterface
            || (is_array($arg) && (count($arg) == 2) && isset($arg[0]) && isset($arg[1]))
            || is_callable($arg);
    }

    /**
     * @param $middleware
     * @return String
     */
    private function getMiddlewareArgName($middleware): string
    {
        if (is_string($middleware)) {
            return $middleware;
        }
        if (is_object($middleware)) {
            return get_class($middleware);
        }
        if (is_array($middleware)) {
            $class = $middleware[0];
            $method = $middleware[1];
            return "$class@$method";
        }
        return "closure";
    }
}
