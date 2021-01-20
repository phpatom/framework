<?php

namespace Atom\Web\Contracts;

interface RendererContract
{
    public function addGlobal(array $data);

    public function render(string $template, array $args = []): string;

    public function addExtensions(RendererExtensionProvider $extensionProvider);
}