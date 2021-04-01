<?php


namespace Atom\Framework\Contracts;

use Atom\Framework\Kernel;

interface HasKernel
{

    public function getKernel(): Kernel;

}
