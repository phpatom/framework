<?php
require_once "../vendor/autoload.php";

use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\DI\Exceptions\UnsupportedInvokerException;
use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;
use Atom\Framework\Application;
use Atom\Framework\Exceptions\RequestHandlerException;
use Atom\Framework\Http\Middlewares\AbstractMiddleware;
use Atom\Framework\Http\RequestHandler;
use Atom\Framework\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @throws Throwable
 * @throws CircularDependencyException
 * @throws ContainerException
 * @throws NotFoundException
 * @throws StorageNotFoundException
 * @throws UnsupportedInvokerException
 * @throws ListenerAlreadyAttachedToEvent
 * @throws RequestHandlerException
 */
function bootstrap()
{
    $app = Application::create(__DIR__);
    $app->get("/", new class extends AbstractMiddleware {

        public function run(ServerRequestInterface $request, RequestHandler $handler): ResponseInterface
        {
            return Response::html("Hello world !");
        }
    });
    $app->run();
}

bootstrap();
