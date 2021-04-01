<?php


namespace Atom\Framework\Events;

use Atom\Event\AbstractEvent;
use Atom\Framework\Kernel;
use Throwable;

class BootFailed extends AbstractEvent
{
    /**
     * @var Kernel
     */
    private Kernel $kernel;
    private Throwable $exception;

    public function __construct(Kernel $kernel, Throwable $exception)
    {
        $this->kernel = $kernel;
        $this->exception = $exception;
    }

    /**
     * @return Kernel
     */
    public function getKernel(): Kernel
    {
        return $this->kernel;
    }

    /**
     * @return Throwable
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }
}
