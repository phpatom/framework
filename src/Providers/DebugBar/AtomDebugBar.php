<?php


namespace Atom\Web\Providers\DebugBar;


use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;
use Atom\Web\Events\MiddlewareLoaded;
use Atom\Web\Providers\DebugBar\Collectors\MiddlewareCollector;
use Atom\Web\Providers\DebugBar\Listeners\MiddlewareLoadedListener;
use Atom\Web\WebApp;
use DebugBar\Bridge\DoctrineCollector;
use DebugBar\DebugBarException;
use DebugBar\StandardDebugBar;


class AtomDebugBar extends StandardDebugBar
{
    /**
     * AtomDebugBar constructor.
     * @param WebApp $app
     * @throws DebugBarException
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     * @throws ListenerAlreadyAttachedToEvent
     */
    public function __construct(WebApp $app)
    {
        parent::__construct();

        $middlewareCollector = new MiddlewareCollector();
        $this->addCollector($middlewareCollector);
        $app->eventDispatcher()->addEventListener(MiddlewareLoaded::class, new MiddlewareLoadedListener($middlewareCollector));
    }

}