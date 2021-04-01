<?php


namespace Atom\Framework\Events;

use Atom\Event\AbstractEvent;

class ServiceProviderRegistered extends AbstractEvent
{
    /**
     * @var string
     */
    private string $className;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

}
