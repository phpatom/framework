<?php


namespace Atom\Framework\Http\Emitter;

use Atom\Framework\Contracts\EmitterContract;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use function count;
use function fastcgi_finish_request;
use function function_exists;
use function in_array;
use function ob_end_clean;
use function ob_end_flush;
use function ob_get_length;
use function ob_get_level;
use function ob_get_status;
use function rtrim;
use function sprintf;
use function str_replace;
use function ucwords;
use function vsprintf;
use const PHP_OUTPUT_HANDLER_CLEANABLE;
use const PHP_OUTPUT_HANDLER_FLUSHABLE;
use const PHP_OUTPUT_HANDLER_REMOVABLE;
use const PHP_SAPI;

/**
 * Class AbstractSapiEmitter
 * @source https://github.com/narrowspark/http-emitter
 */
abstract class AbstractSapiEmitter implements EmitterContract
{
    protected function assertNoPreviousOutput(): void
    {
        $file = $line = null;

        if (headers_sent($file, $line)) {
            throw new  RuntimeException(sprintf(
                'Unable to emit response: Headers already sent in file %s on line %s. '
                . 'This happens if echo, print, printf, print_r, var_dump, var_export or similar statement that writes
                 to the output buffer are used.',
                $file,
                (string)$line
            ));
        }

        if (ob_get_level() > 0 && ob_get_length() > 0) {
            throw new RuntimeException('Output has been emitted previously; cannot emit response.');
        }
    }

    /**
     * @param ResponseInterface $response
     */
    protected function emitStatusLine(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        header(
            vsprintf(
                'HTTP/%s %d%s',
                [
                    $response->getProtocolVersion(),
                    $statusCode,
                    rtrim(' ' . $response->getReasonPhrase()),
                ]
            ),
            true,
            $statusCode
        );
    }

    /**
     * @param ResponseInterface $response
     */
    protected function emitHeaders(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        foreach ($response->getHeaders() as $header => $values) {
            $name = $this->toWordCase($header);
            $first = $name !== 'Set-Cookie';

            foreach ($values as $value) {
                header(
                    sprintf(
                        '%s: %s',
                        $name,
                        $value
                    ),
                    $first,
                    $statusCode
                );

                $first = false;
            }
        }
    }

    /**
     * Converts header names to wordcase.
     *
     * @param string $header
     *
     * @return string
     */
    protected function toWordCase(string $header): string
    {
        $filtered = str_replace('-', ' ', $header);
        $filtered = ucwords($filtered);

        return str_replace(' ', '-', $filtered);
    }

    /**
     * Flushes output buffers and closes the connection to the client,
     * which ensures that no further output can be sent.
     *
     * @return void
     */
    protected function closeConnection(): void
    {
        if (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
            self::closeOutputBuffers(0, true);
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    public static function closeOutputBuffers(int $maxBufferLevel, bool $flush): void
    {
        $status = ob_get_status(true);
        $level = count($status);
        $flags = PHP_OUTPUT_HANDLER_REMOVABLE |
            ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE);

        while ($level-- > $maxBufferLevel &&
            (bool)($s = $status[$level]) && ($s['del'] ?? !isset($s['flags']) || $flags === ($s['flags'] & $flags))) {
            if ($flush) {
                ob_end_flush();
            } else {
                ob_end_clean();
            }
        }
    }
}
