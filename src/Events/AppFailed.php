<?php


namespace Atom\Web\Events;

use Atom\Event\AbstractEvent;
use Atom\Web\Http\Request;
use Atom\Web\Http\RequestHandler;
use Exception;
use Psr\Http\Message\ServerRequestInterface;

class AppFailed extends AbstractEvent
{

    /**
     * @var Exception
     */
    private $exception;
    /**
     * @var RequestHandler
     */
    private $handler;
    /**
     * @var Request
     */
    private $request;

    public function __construct(RequestHandler $handler, Exception $exception, ServerRequestInterface $request)
    {
        $this->exception = $exception;
        $this->handler = $handler;
        $this->request = $request;
    }

    /**
     * @return Exception
     */
    public function getException(): Exception
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
