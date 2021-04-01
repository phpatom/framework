<?php

use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;
use Atom\Framework\Application;
use Atom\Framework\ApplicationFactory;
use Atom\Framework\Contracts\HasKernel;
use Atom\Framework\Env;

if (!function_exists("atom")) {
    /**
     * @param string $dir
     * @param string $env
     * @return Application
     * @throws Throwable
     * @throws ListenerAlreadyAttachedToEvent
     */
    function atom(string $dir, string $env = Env::DEV): Application
    {
        return Application::create($dir, $env);
    }
}
if (!function_exists("app")) {
    /**
     * @param HasKernel $kernel
     * @return Application
     * @throws ListenerAlreadyAttachedToEvent
     * @throws Throwable
     */
    function app(HasKernel $kernel): Application
    {
        return Application::of($kernel);
    }
}
if (function_exists("createAtom")) {
    /**
     * @return ApplicationFactory
     */
    function createAtom(): ApplicationFactory
    {
        return new ApplicationFactory();
    }
}
