<?php

namespace Atom\Web\Contracts;

use Psr\Http\Message\ResponseInterface;

interface RendererContract
{
    public function render(string $path, array $args = []):ResponseInterface;
}