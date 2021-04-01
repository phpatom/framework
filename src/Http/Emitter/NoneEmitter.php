<?php


namespace Atom\Framework\Http\Emitter;

use Atom\Framework\Contracts\EmitterContract;
use Psr\Http\Message\ResponseInterface;

final class NoneEmitter implements EmitterContract
{

    public function emit(ResponseInterface $response): void
    {
    }
}
