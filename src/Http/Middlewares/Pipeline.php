<?php

namespace Atom\Framework\Http\Middlewares;

use Atom\Framework\Exceptions\RequestHandlerException;
use Atom\Framework\Http\RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Pipeline extends AbstractMiddleware
{

    /**
     * @var array
     */
    private array $middlewares;

    public function __construct(array $middlewares)
    {
        $this->middlewares = $middlewares;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandler $handler
     * @return ResponseInterface
     * @throws RequestHandlerException
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
