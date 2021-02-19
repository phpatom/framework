<?php

namespace Atom\Web\Http\Emitter;

use Psr\Http\Message\ResponseInterface;

final class SapiEmitter extends AbstractSapiEmitter
{
    /**
     * @param ResponseInterface $response
     */
    public function emit(ResponseInterface $response): void
    {
        $this->assertNoPreviousOutput();

        $this->emitHeaders($response);

        // Set the status _after_ the headers, because of PHP's "helpful" behavior with location headers.
        $this->emitStatusLine($response);

        $this->emitBody($response);

        $this->closeConnection();
    }

    /**
     * Sends the message body of the response.
     *
     * @param ResponseInterface $response
     */
    private function emitBody(ResponseInterface $response): void
    {
        echo $response->getBody();
    }
}
