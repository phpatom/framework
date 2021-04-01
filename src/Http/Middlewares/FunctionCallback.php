<?php


namespace Atom\Framework\Http\Middlewares;

use Atom\DI\Definition;
use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\Framework\Http\RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;

class FunctionCallback extends AbstractMiddleware
{
    use DefinitionToResponseTrait;

    /**
     * @var callable
     */
    private $callback;
    /**
     * @var array
     */
    private array $args;

    /**
     * @var array
     */
    private array $mapping;


    /**
     * FunctionCallback constructor.
     * @param callable $callback
     * @param array $args
     * @param array $mapping
     */
    public function __construct(callable $callback, array $args = [], array $mapping = [])
    {

        $this->callback = $callback;
        $this->args = $args;
        $this->mapping = $mapping;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandler $handler
     * @return ResponseInterface
     * @throws ContainerException
     */
    public function run(ServerRequestInterface $request, RequestHandler $handler): ResponseInterface
    {
        return self::call($this->callback, $request, $handler, $this->args, $this->mapping);
    }

    /**
     * @param callable $callable
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
    public static function call(
        callable $callable,
        ServerRequestInterface $request,
        RequestHandler $handler,
        array $args = [],
        array $mapping = []
    ): ?ResponseInterface {
        $definition = Definition::callTo($callable)->function();
        return self::definitionToResponse($definition, $request, $handler, $args, $mapping);
    }
}
