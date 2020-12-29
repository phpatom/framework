<?php
use Atom\App\Env\Env;
use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;
use Atom\Web\WebApp;

if (!function_exists("createWebApp")) {
    /**
     * @param string $dir
     * @param string $env
     * @return WebApp
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    function createWebApp(string $dir, string $env = Env::DEV): WebApp
    {
        return WebApp::create($dir, $env);
    }
}
