<?php


namespace Atom\Web\Middlewares;

use Atom\DI\DIC;
use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\Routing\Contracts\RouterContract;
use Atom\Routing\MatchedRoute;
use Atom\Web\AbstractMiddleware;
use Atom\Web\Exceptions\InvalidRouteHandlerException;
use Atom\Web\Exceptions\RequestHandlerException;
use Atom\Web\RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class DispatchRoutes extends AbstractMiddleware
{
    /**
     * @var RouterContract
     */
    private $router;
    /**
     * @var DIC
     */
    private $dic;

    public function __construct(RouterContract $router, DIC $dic)
    {
        $this->router = $router;
        $this->dic = $dic;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandler $handler
     * @return ResponseInterface
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws InvalidRouteHandlerException
     * @throws NotFoundException
     * @throws RequestHandlerException
     * @throws StorageNotFoundException
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
     * @param DIC $dic
     * @return mixed|void
     * @throws InvalidRouteHandlerException
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    private function asMiddleware(
        $routeHandler,
        MatchedRoute $matchedRoute,
        DIC $dic
    ): ?MiddlewareInterface {
        if ($routeHandler == null) {
            return null;
        }
        if (is_string($routeHandler)) {
            return $dic->get($routeHandler);
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
