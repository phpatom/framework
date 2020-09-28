<?php


namespace Atom\Web\Middlewares;

use Atom\DI\Exceptions\ContainerException;
use Atom\Web\AbstractMiddleware;
use Atom\Web\RequestHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MethodCallback extends AbstractMiddleware
{
    use DefinitionToResponseTrait;

    private $object;
    /**
     * @var string
     */
    private $method;
    /**
     * @var array
     */
    private $args;
    /**
     * @var array
     */
    private $mapping;

    public function __construct($object, string $method, array $args = [], array $mapping = [])
    {

        $this->object = $object;
        $this->method = $method;
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
        return self::call($this->object, $this->method, $request, $handler, $this->args, $this->mapping);
    }

    /**
     * @param $object
     * @param string $method
     * @param ServerRequestInterface $request
     * @param RequestHandler $handler
     * @param array $args
     * @param array $mapping
     * @return mixed
     */
    public static function call(
        $object,
        string $method,
        ServerRequestInterface $request,
        RequestHandler $handler,
        array $args = [],
        array $mapping = []
    ): ?ResponseInterface {
        $definition = $handler->container()->as()->callTo($method)->method()->on($object);
        return self::definitionToResponse($definition, $request, $handler, $args, $mapping);
    }
}
