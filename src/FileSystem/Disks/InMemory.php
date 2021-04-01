<?php


namespace Atom\Framework\FileSystem\Disks;

use Atom\Framework\Contracts\DiskContract;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

class InMemory implements DiskContract
{
    /**
     * @var string
     */
    private string $label;
    /**
     * @var ?array
     */
    private ?array $config;

    public function __construct(string $label, ?array $config = null)
    {
        $this->label = $label;
        $this->config = $config;
    }

    public function getAdapter(): FilesystemAdapter
    {
        return new InMemoryFilesystemAdapter();
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getConfig(): ?array
    {
        return $this->config;
    }
}
