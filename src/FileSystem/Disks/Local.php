<?php


namespace Atom\Framework\FileSystem\Disks;

use Atom\Framework\Contracts\DiskContract;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\MimeTypeDetection\MimeTypeDetector;

class Local implements DiskContract
{
    /**
     * @var int
     */
    const SKIP_LINKS = 0001;

    /**
     * @var int
     */
    const DISALLOW_LINKS = 0002;

    /**
     * @var array
     */
    private array $config;
    /**
     * @var string
     */
    private string $path;

    private string $label;
    /**
     * @var int
     */
    private int $writeFlags;
    /**
     * @var int
     */
    private int $linkHandling;

    /**
     * @var ?VisibilityConverter
     */
    private ?VisibilityConverter $visibilityConverter;

    private ?MimeTypeDetector $mimeTypeDetector;

    /**
     * Local constructor.
     * @param string $path
     * @param String $label
     * @param MimeTypeDetector|null $mimeTypeDetector
     * @param ?VisibilityConverter $visibilityConverter
     * @param int $writeFlags
     * @param int $linkHandling
     */
    public function __construct(
        string $path,
        string $label,
        MimeTypeDetector $mimeTypeDetector,
        int $writeFlags = LOCK_EX,
        int $linkHandling = self::DISALLOW_LINKS,
        ?VisibilityConverter $visibilityConverter = null
    ) {
        $this->path = $path;
        $this->label = $label;
        $this->writeFlags = $writeFlags;
        $this->linkHandling = $linkHandling;
        $this->visibilityConverter = $visibilityConverter;
        $this->mimeTypeDetector = $mimeTypeDetector;
    }

    public function getAdapter(): FilesystemAdapter
    {
        return new LocalFilesystemAdapter(
            $this->path,
            $this->visibilityConverter,
            $this->writeFlags,
            $this->linkHandling,
        );
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getConfig(): ?array
    {
        return $this->config;
    }

    /**
     * @param array $config
     * @return Local
     */
    public function withConfig(array $config): Local
    {
        $this->config = $config;
        return $this;
    }
}
