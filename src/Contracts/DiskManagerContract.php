<?php


namespace Atom\Framework\Contracts;

use Atom\Framework\FileSystem\DiskNotFoundException;
use League\Flysystem\Filesystem;

interface DiskManagerContract
{
    /**
     * @param string $label
     * @return Filesystem
     * @throws DiskNotFoundException
     */
    public function get(string $label): Filesystem;

    public function has(string $label): bool;

    /**
     * @param $label
     * @throws DiskNotFoundException
     */
    public function remove($label);

    /**
     * @param string $label
     * @return Filesystem
     * @throws DiskNotFoundException
     */
    public function disk(string $label): Filesystem;
}
