<?php


namespace Atom\Framework\Http\Middlewares;

use Atom\DI\Container;
use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\Framework\Exceptions\InvalidRouteHandlerException;
use Atom\Framework\Exceptions\RequestHandlerException;
use Atom\Framework\Http\RequestHandler;
use Atom\Routing\Contracts\RouterContract;
use Atom\Routing\MatchedRoute;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionException;

class DispatchRoutes extends AbstractMiddleware
{
    /**
     * @var RouterContract
     */
    private RouterContract $router;
    /**
     * @var Container
     */
    private Container $Container;

    public function __construct(RouterContract $router, Container $Container)
    {
        $this->router = $router;
        $this->Container = $Container;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandler $handler
     * @return ResponseInterface
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws InvalidRouteHandlerException
     * @throws NotFoundException
     * @throws ReflectionException
     * @throws RequestHandlerException
     */
    public function run(ServerRequestInterface $request, RequestHandler $handler): ResponseInterface
    {
        $request = $this->router->dispatch($request);
        $matchedRoute = MatchedRoute::of($request);
        $routeHandler = $matchedRoute->getRoute()->getHandler();
        $routeGroup = $matchedRoute->getRoute()->getRouteGroup();
        $groupHandler = $routeGroup != null ? $routeGroup->getHandler() : null;
        $routeHandlers = [$groupHandler, $routeHandler];
        $middlewares = [];
        foreach ($routeHandlers as $routeHandler) {
            $middleware = $this->asMiddleware($routeHandler, $matchedRoute, $handler->container());
            if ($middleware != null) {
                $middlewares[] = $middleware;
            }
        }
        $handler->load($middlewares);
        return $handler->handle($request);
    }

    /**
     * @param $routeHandler
     * @param MatchedRoute $matchedRoute
     * @param Container $Container
     * @return mixed|void
     * @throws InvalidRouteHandlerException
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException|ReflectionException
     */
    private function asMiddleware(
        $routeHandler,
        MatchedRoute $matchedRoute,
        Container $Container
    ): ?MiddlewareInterface
    {
        if ($routeHandler == null) {
            return null;
        }
        if (is_string($routeHandler)) {
            return $Container->get($routeHandler);
        }
        if (($routeHandler instanceof MiddlewareInterface)) {
            return $routeHandler;
        }
        if (is_callable($routeHandler) && !is_array($routeHandler)) {
            return new FunctionCallback(
                $routeHandler,
                array_merge(["match" => $matchedRoute], $matchedRoute->getParameters()),
                [MatchedRoute::class => $matchedRoute]
            );
        }
        if (is_array($routeHandler) && (count($routeHandler) == 2)) {
            $controller = $routeHandler[0];
            $method = $routeHandler[1];
            return new MethodCallback(
                $controller,
                $method,
                array_merge(["match" => $matchedRoute], $matchedRoute->getParameters()),
                [MatchedRoute::class => $matchedRoute]
            );
        }
        throw new InvalidRouteHandlerException($matchedRoute);
    }
}
