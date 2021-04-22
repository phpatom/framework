<?php


namespace Atom\Framework\Http\Middlewares;

use Atom\DI\Definition;
use Atom\DI\Definitions\AbstractDefinition;
use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\Framework\Contracts\RendererContract;
use Atom\Framework\Http\RequestHandler;
use Atom\Framework\Http\Response;
use Atom\Framework\Http\ResponseSender;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;

trait DefinitionToResponseTrait
{
    /**
     * @param AbstractDefinition $definition
     * @param ServerRequestInterface $request
     * @param RequestHandler $handler
     * @param array $args
     * @param array $mapping
     * @return mixed
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public static function definitionToResponse(
        AbstractDefinition $definition,
        ServerRequestInterface $request,
        RequestHandler $handler,
        array $args = [],
        array $mapping = []
    ) {
        $c = $handler->container();
        $definition
            ->withParameters($args)
            ->withClasses($mapping)
            ->withParameters([
                "rend" => Definition::get(RendererContract::class),
                "hand" => $handler,
                "req" => $request,
                "ker" => $handler->getKernel(),
                "res" => Definition::get(ResponseSender::class)
            ]);
        $c->getResolutionStack()->clear();
        $response = $c->interpret($definition);
        $c->getResolutionStack()->clear();
        if (is_array($response)) {
            return Response::json($response);
        }
        if (is_string($response)) {
            return Response::html($response);
        }
        if (is_scalar($response)) {
            return Response::text($response);
        }
        return $response;
    }
}
