<?php


namespace Atom\Framework\Events;

use Atom\Event\AbstractEvent;
use Atom\Framework\Http\Request;
use Atom\Framework\Http\RequestHandler;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class AppFailed extends AbstractEvent
{

    /**
     * @var Throwable
     */
    private Throwable $exception;
    /**
     * @var RequestHandler
     */
    private RequestHandler $handler;
    /**
     * @var Request
     */
    private $request;

    public function __construct(RequestHandler $handler, Throwable $exception, ServerRequestInterface $request)
    {
        $this->exception = $exception;
        $this->handler = $handler;
        $this->request = $request;
    }

    /**
     * @return Throwable
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }

    /**
     * @return RequestHandler
     */
    public function getHandler(): RequestHandler
    {
        return $this->handler;
    }

    /**
     * @return Request
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
