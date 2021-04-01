<?php

declare(strict_types=1);

namespace Atom\Framework\Http\Emitter;

use Psr\Http\Message\ResponseInterface;
use function connection_status;
use function is_array;
use function preg_match;
use function strlen;
use function substr;
use const CONNECTION_NORMAL;

/**
 * Class SapiStreamEmitter
 * @source https://github.com/narrowspark/http-emitter
 */
final class SapiStreamEmitter extends AbstractSapiEmitter
{
    /**
     * Maximum output buffering size for each iteration.
     *
     * @var int
     */
    protected int $maxBufferLength = 8192;

    /**
     * Set the maximum output buffering level.
     *
     * @param int $maxBufferLength
     *
     * @return self
     */
    public function setMaxBufferLength(int $maxBufferLength): self
    {
        $this->maxBufferLength = $maxBufferLength;

        return $this;
    }

    /**
     * @param ResponseInterface $response
     */
    public function emit(ResponseInterface $response): void
    {
        $this->assertNoPreviousOutput();

        $this->emitHeaders($response);

        // Set the status _after_ the headers, because of PHP's "helpful" behavior with location headers.
        $this->emitStatusLine($response);

        $range = $this->parseContentRange($response->getHeaderLine('Content-Range'));

        if (is_array($range) && $range[0] === 'bytes') {
            $this->emitBodyRange($range, $response, $this->maxBufferLength);
        } else {
            $this->emitBody($response, $this->maxBufferLength);
        }

        $this->closeConnection();
    }

    /**
     * Sends the message body of the response.
     *
     * @param ResponseInterface $response
     * @param int $maxBufferLength
     */
    private function emitBody(ResponseInterface $response, int $maxBufferLength): void
    {
        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        if (!$body->isReadable()) {
            echo $body;

            return;
        }

        while (!$body->eof()) {
            echo $body->read($maxBufferLength);

            if (connection_status() !== CONNECTION_NORMAL) {
                break;
            }
        }
    }

    /**
     * Emit a range of the message body.
     *
     * @param array $range
     * @param ResponseInterface $response
     * @param int $maxBufferLength
     */
    private function emitBodyRange(array $range, ResponseInterface $response, int $maxBufferLength): void
    {
        [$unit, $first, $last, $length] = $range;

        $body = $response->getBody();

        $length = $last - $first + 1;

        if ($body->isSeekable()) {
            $body->seek($first);
            $first = 0;
        }

        if (!$body->isReadable()) {
            echo substr($body->getContents(), $first, (int)$length);

            return;
        }

        $remaining = $length;

        while ($remaining >= $maxBufferLength && !$body->eof()) {
            $contents = $body->read($maxBufferLength);
            $remaining -= strlen($contents);

            echo $contents;

            if (connection_status() !== CONNECTION_NORMAL) {
                break;
            }
        }

        if ($remaining > 0 && !$body->eof()) {
            echo $body->read((int)$remaining);
        }
    }

    /**
     * Parse content-range header
     * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.16.
     *
     * @param string $header
     *
     * @return null|array [unit, first, last, length]; returns false if no
     *                    content range or an invalid content range is provided
     */
    private function parseContentRange(string $header): ?array
    {
        if (preg_match(
            '/(?P<unit>[\w]+)\s+(?P<first>\d+)-(?P<last>\d+)\/(?P<length>\d+|\*)/',
            $header,
            $matches
        ) === 1) {
            return [
                $matches['unit'],
                (int)$matches['first'],
                (int)$matches['last'],
                $matches['length'] === '*' ? '*' : (int)$matches['length'],
            ];
        }

        return null;
    }
}
