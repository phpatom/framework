<?php

namespace Atom\Framework\FileSystem;

use Atom\Framework\Contracts\DiskContract;
use Atom\Framework\Contracts\DiskManagerContract;
use League\Flysystem\Filesystem;

class DiskManager implements DiskManagerContract
{
    /**
     * DiskManager constructor.
     * @param DiskContract[] $disks
     */
    public function __construct(array $disks = [])
    {
        foreach ($disks as $disk) {
            $this->add($disk);
        }
    }

    /**
     * @var Filesystem []
     */
    private array $disks = [];

    protected function add(DiskContract $disk)
    {
        $this->disks[$disk->getLabel()] = new Filesystem($disk->getAdapter(), $disk->getConfig());
    }

    /**
     * @param string $label
     * @return Filesystem
     * @throws DiskNotFoundException
     */
    public function get(string $label): Filesystem
    {
        if (!$this->has($label)) {
            throw new DiskNotFoundException("the disk [$label] was not found");
        }
        return $this->disks[$label];
    }

    public function has(string $label): bool
    {
        return array_key_exists($label, $this->disks);
    }

    /**
     * @param $label
     * @throws DiskNotFoundException
     */
    public function remove($label)
    {
        if (!$this->has($label)) {
            throw new DiskNotFoundException("the disk [$label] was not found");
        }
        unset($this->disks[$label]);
    }

    /**
     * @param string $label
     * @return Filesystem
     * @throws DiskNotFoundException
     */
    public function disk(string $label): Filesystem
    {
        return $this->get($label);
    }
}
