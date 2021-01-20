<?php


namespace Atom\Framework\Events;

use Atom\Kernel\Contracts\ServiceProviderContract;
use Atom\Event\AbstractEvent;
use Throwable;

class ServiceProviderFailed extends AbstractEvent
{
    /**
     * @var ServiceProviderContract
     */
    private $contract;
    /**
     * @var Throwable
     */
    private $exception;

    public function __construct(ServiceProviderContract $contract, Throwable $exception)
    {
        $this->contract = $contract;
        $this->exception = $exception;
    }

    /**
     * @return Throwable
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }

    /**
     * @return ServiceProviderContract
     */
    public function getContract(): ServiceProviderContract
    {
        return $this->contract;
    }
}
