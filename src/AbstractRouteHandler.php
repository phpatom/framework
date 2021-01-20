<?php

namespace Atom\Framework;

use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\Routing\Exceptions\RouteNotFoundException;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractRouteHandler extends AbstractMiddleware
{
    /**
     * @var RequestHandler
     */
    protected $handler;
    private $controllerMethods = [
        RequestMethodInterface::METHOD_HEAD,
        RequestMethodInterface::METHOD_GET,
        RequestMethodInterface::METHOD_POST,
        RequestMethodInterface::METHOD_PUT,
        RequestMethodInterface::METHOD_PATCH,
        RequestMethodInterface::METHOD_DELETE,
        RequestMethodInterface::METHOD_PURGE,
        RequestMethodInterface::METHOD_OPTIONS,
        RequestMethodInterface::METHOD_TRACE,
        RequestMethodInterface::METHOD_CONNECT,
    ];

    public function run(ServerRequestInterface $request, RequestHandler $handler): ResponseInterface
    {
        $this->handler = $handler;

        foreach ($this->controllerMethods as $methods) {
            if ($request->getMethod() == $methods) {
                return $this->{"do" . ucfirst(strtolower($methods))}($request, $handler);
            }
        }
        return new EmptyResponse();
    }

    /**
     * @param string $view
     * @param array $data
     * @param int $statusCode
     * @param array $headers
     * @return ResponseInterface
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    protected function render(
        string $view,
        array $data = [],
        int $statusCode = 200,
        array $headers = []
    ): ResponseInterface {
        return Response::html($this->handler->renderer()->render($view, $data), $statusCode, $headers);
    }

    public function json($data, int $status = 200, array $headers = []): JsonResponse
    {
        return Response::json($data, $status, $headers);
    }

    public function text($data, int $status = 200, array $headers = []): TextResponse
    {
        return Response::text($data, $status, $headers);
    }

    /**
     * @param string $uri
     * @param int $status
     * @param array $headers
     * @return RedirectResponse
     */
    public function redirect(string $uri, int $status = 200, array $headers = []): RedirectResponse
    {
        return Response::redirect($uri, $status, $headers);
    }

    /**
     * @param string $route
     * @param array $data
     * @param int $status
     * @param array $headers
     * @return RedirectResponse
     * @throws RouteNotFoundException
     */
    public function redirectRoute(string $route, array $data, int $status = 200, array $headers = []): RedirectResponse
    {
        return Response::redirectRoute($route, $data, $status, $headers);
    }

    public function doGet(ServerRequestInterface $request, RequestHandler $handler)
    {
    }

    public function doPost(ServerRequestInterface $request, RequestHandler $handler)
    {
    }

    public function doPut(ServerRequestInterface $request, RequestHandler $handler)
    {
    }

    public function doPatch(ServerRequestInterface $request, RequestHandler $handler)
    {
    }

    public function doDelete(ServerRequestInterface $request, RequestHandler $handler)
    {
    }
}
