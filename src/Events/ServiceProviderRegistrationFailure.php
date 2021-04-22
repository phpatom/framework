<?php


namespace Atom\Framework\Events;

use Atom\Framework\Contracts\ServiceProviderContract;
use Atom\Event\AbstractEvent;
use Throwable;

class ServiceProviderRegistrationFailure extends AbstractEvent
{
    /**
     * @var ServiceProviderContract
     */
    private ServiceProviderContract $contract;
    /**
     * @var Throwable
     */
    private Throwable $exception;

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
