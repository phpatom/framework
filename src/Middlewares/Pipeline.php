<?php

namespace Atom\Web\Middlewares;

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
     */
    public function run(ServerRequestInterface $request, RequestHandler $handler): ResponseInterface
    {
        $handler->load($this->middlewares);
        return $handler->handle($request);
    }

    public static function create(array $middlewares)
    {
        return new self($middlewares);
    }
}
