<?php


namespace Atom\Web\Events;

use Atom\Event\AbstractEvent;
use Psr\Http\Server\MiddlewareInterface;

class MiddlewareLoaded extends AbstractEvent
{
    /**
     * @var MiddlewareInterface
     */
    private $middleware;

    public function __construct(MiddlewareInterface $middleware)
    {
        $this->middleware = $middleware;
    }

    public function getRelatedMiddleware(): MiddlewareInterface
    {
        return $this->middleware;
    }
}
