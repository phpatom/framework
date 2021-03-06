<?php

use Atom\Kernel\Env\Env;
use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;
use Atom\Web\Application;

if (!function_exists("atom")) {
    /**
     * @param string $dir
     * @param string $env
     * @return Application
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    function atom(string $dir, string $env = Env::DEV): Application
    {
        return Application::create($dir, $env);
    }
}
