<?php


namespace Atom\Web;

use Atom\DI\DIC;
use Atom\Web\Contracts\ModuleContract;
use Atom\Web\Contracts\RendererContract;
use Atom\Web\Events\MiddlewareLoaded;
use Atom\Web\Exceptions\RequestHandlerException;
use Atom\Routing\Contracts\RouterContract;
use Atom\Routing\Router;
use Atom\Web\Middlewares\DispatchRoutes;
use Laminas\Diactoros\ServerRequestFactory;
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
    )
    {
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
     * @throws RequestHandlerException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->started) {
            /**
             * @var $module ModuleContract
             */
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
    }

    /**
     * @return MiddlewareInterface |null
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
     */
    private function build($middleware): ?MiddlewareInterface
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

    public function renderer()
    {
        if (!$this->renderer) {
            $this->renderer = $this->container->get(RendererContract::class);
        }
        return $this->renderer;
    }

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
    private function isValidIndex($index)
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
    private function isValidMiddlewareArg($arg)
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
     * @throws RequestHandlerException
     */
    public function run()
    {
        $response = $this->handle(ServerRequestFactory::fromGlobals());
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
