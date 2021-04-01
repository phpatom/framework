<?php


namespace Atom\Framework\FileSystem;

class Path
{
    /**
     * @var string
     */
    private string $publicPath;
    /**
     * @var string
     */
    private string $appPath;

    public function __construct(string $appPath, ?string $publicPath = null)
    {
        $this->appPath = $this->removeTrailingSlash($appPath);
        if (!$publicPath) {
            $publicPath = $this->join($this->appPath, "public");
        }
        $this->publicPath = $this->removeTrailingSlash($publicPath);
    }

    public function join(...$paths): string
    {
        return implode(DIRECTORY_SEPARATOR, $paths ?? []);
    }

    /**
     * @param array $paths
     * @return string
     */
    public function app(...$paths): string
    {
        if (!empty($paths)) {
            return $this->join(...array_merge([$this->appPath], $paths));
        }
        return $this->appPath;
    }

    private function removeTrailingSlash(string $path): string
    {
        return rtrim(rtrim($path, "/"), "\\");
    }

    public function __toString(): string
    {
        return $this->app();
    }

    /**
     * @param mixed ...$paths
     * @return string
     */
    public function public(...$paths): string
    {
        if (!empty($paths)) {
            return $this->join(...array_merge([$this->publicPath], $paths));
        }
        return $this->publicPath;
    }
}
