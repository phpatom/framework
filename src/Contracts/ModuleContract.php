<?php

namespace Atom\Framework\Contracts;

interface ModuleContract
{
    public function getModuleName(): string;

    public function getModuleDescription(): string;

    public function bootstrap();
}
