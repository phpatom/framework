<?php


namespace Atom\Framework\FileSystem;

use Atom\Framework\Kernel;
use Atom\Framework\Contracts\ServiceProviderContract;
use Atom\DI\Exceptions\StorageNotFoundException;

class PathProvider implements ServiceProviderContract
{
    /**
     * @var string
     */
    private $appPath;


    public function __construct(string $appPath)
    {

        $this->appPath = $appPath;
    }

    /**
     * @param Kernel $app
     * @throws StorageNotFoundException
     */
    public function register(Kernel $app)
    {
        $c = $app->container();
        $c->singletons()->store(
            Path::class,
            $c->as()->object(new Path($this->appPath))
        );
    }
}
