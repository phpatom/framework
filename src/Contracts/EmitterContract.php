<?php


namespace Atom\Web\Contracts;

use Psr\Http\Message\ResponseInterface;

interface EmitterContract
{
    public function emit(ResponseInterface $response): void;
}
