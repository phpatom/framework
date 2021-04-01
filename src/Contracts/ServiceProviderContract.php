<?php


namespace Atom\Framework\Contracts;

use Atom\Framework\Kernel;

interface ServiceProviderContract
{
    public function register(Kernel $kernel);
}
