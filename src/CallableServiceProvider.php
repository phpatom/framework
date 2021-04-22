<?php


namespace Atom\Framework;

class CallableServiceProvider extends ServiceProvider
{
    /**
     * @var callable
     */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function register(Kernel $kernel)
    {
        $callable = $this->callable;
        call_user_func_array($callable, [$kernel]);
    }

    /**
     * @return callable
     */
    public function getCallable(): callable
    {
        return $this->callable;
    }
}
