<?php


namespace Atom\Web\Http\Emitter;

use Atom\Web\Contracts\EmitterContract;
use Psr\Http\Message\ResponseInterface;

final class NoneEmitter implements EmitterContract
{

    public function emit(ResponseInterface $response): void
    {
    }
}
