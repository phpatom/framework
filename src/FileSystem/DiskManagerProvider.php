<?php

namespace Atom\Framework\FileSystem;

use Atom\Framework\Contracts\DiskContract;
use Atom\Framework\Kernel;
use Atom\Framework\Contracts\ServiceProviderContract;
use Atom\DI\Exceptions\StorageNotFoundException;

class DiskManagerProvider implements ServiceProviderContract
{
    /**
     * @var DiskContract[]
     */
    private array $disks = [];

    public function __construct(array $disks)
    {
        $this->disks = $disks;
    }

    /**
     * @param Kernel $app
     * @throws StorageNotFoundException
     */
    public function register(Kernel $app)
    {
        $c = $app->container();
        $c->singletons()->store(DiskManager::class, $c->as()->object(new DiskManager($this->disks)));
    }
}
