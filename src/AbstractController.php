<?php

namespace Atom\Web;

use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractController extends AbstractMiddleware
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

    protected function render(string $view, array $data = [])
    {
        return $this->handler->renderer()->render($view, $data);
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
