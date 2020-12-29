<?php


namespace Atom\Web\Events;

use Atom\App\Contracts\ServiceProviderContract;
use Atom\Event\AbstractEvent;

class ServiceProviderFailed extends AbstractEvent
{
    /**
     * @var ServiceProviderContract
     */
    private $contract;
    /**
     * @var \Throwable
     */
    private $exception;

    public function __construct(ServiceProviderContract $contract, \Throwable $exception)
    {
        $this->contract = $contract;
        $this->exception = $exception;
    }

    /**
     * @return \Throwable
     */
    public function getException(): \Throwable
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
