<?php

namespace Atom\Framework\Contracts;

use League\Flysystem\FilesystemAdapter;

interface DiskContract
{
    public function getAdapter(): FilesystemAdapter;

    public function getLabel(): string;

    public function getConfig(): ?array;
}
