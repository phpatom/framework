<?php

namespace Atom\Web\Middlewares;

use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\Web\AbstractMiddleware;
use Atom\Web\Exceptions\RequestHandlerException;
use Atom\Web\RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Pipeline extends AbstractMiddleware
{

    /**
     * @var array
     */
    private $middlewares;

    public function __construct(array $middlewares)
    {
        $this->middlewares = $middlewares;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandler $handler
     * @return ResponseInterface
     * @throws RequestHandlerException
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    public function run(ServerRequestInterface $request, RequestHandler $handler): ResponseInterface
    {
        $handler->load($this->middlewares);
        return $handler->handle($request);
    }

    public static function create(array $middlewares): Pipeline
    {
        return new self($middlewares);
    }
}
