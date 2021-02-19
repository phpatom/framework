<?php


namespace Atom\Web\Http\Middlewares;

use Atom\DI\Contracts\DefinitionContract;
use Atom\DI\Exceptions\ContainerException;
use Atom\Web\Contracts\RendererContract;
use Atom\Web\Request;
use Atom\Web\RequestHandler;
use Atom\Web\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

trait DefinitionToResponseTrait
{
    /**
     * @param DefinitionContract $definition
     * @param ServerRequestInterface $request
     * @param RequestHandler $handler
     * @param array $args
     * @param array $mapping
     * @return mixed
     * @throws ContainerException
     */
    public static function definitionToResponse(
        DefinitionContract $definition,
        ServerRequestInterface $request,
        RequestHandler $handler,
        array $args = [],
        array $mapping = []
    )
    {
        $c = $handler->container();
        $requestStorable = $c->as()->object($request);
        $handlerStorable = $c->as()->object($handler);
        $c->bindings()->store(ServerRequestInterface::class, $requestStorable);
        $c->bindings()->store(Request::class, $requestStorable);

        $definition
            ->with(RequestHandler::class, $handlerStorable)
            ->with(RequestHandlerInterface::class, $handlerStorable)
            ->withParameter("renderer", $c->as()->get(RendererContract::class))
            ->withParameter("requestHandler", $handlerStorable);
        foreach ($args as $name => $value) {
            $definition->withParameter($name, $c->as()->value($value));
        }
        foreach ($mapping as $abstract => $concrete) {
            $definition->with($abstract, $c->as()->value($concrete));
        }
        $c->getExtractionChain()->clear();
        $response = $c->extract($definition);
        $c->getExtractionChain()->clear();
        if (is_string($response)) {
            return Response::html($response);
        }
        if (is_array($response)) {
            return Response::json($response);
        }
        return $response;
    }
}
