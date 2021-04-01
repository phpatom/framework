<?php


namespace Atom\Framework\Http;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\InjectContentTypeTrait;
use Laminas\Diactoros\Stream;

class JsonStringResponse extends Response
{
    use InjectContentTypeTrait;

    const DEFAULT_JSON_FLAGS = 79;

    /**
     * @var mixed
     */
    private $payload;

    /**
     * @var int
     */
    private int $encodingOptions;

    public function __construct(
        string $data,
        int $status = 200,
        array $headers = [],
        int $encodingOptions = self::DEFAULT_JSON_FLAGS
    )
    {
        $this->setPayload($data);
        $this->encodingOptions = $encodingOptions;
        $body = $this->createBodyFromJson($data);
        $headers = $this->injectContentType('application/json', $headers);
        parent::__construct($body, $status, $headers);
    }

    /**
     * @return string
     */
    public function getPayload(): string
    {
        return $this->payload;
    }

    public function getEncodingOptions(): int
    {
        return $this->encodingOptions;
    }

    public function withEncodingOptions(int $encodingOptions): JsonStringResponse
    {
        $new = clone $this;
        $new->encodingOptions = $encodingOptions;
        return $this->updateBodyFor($new);
    }

    private function createBodyFromJson(string $json): Stream
    {
        $body = new Stream('php://temp', 'wb+');
        $body->write($json);
        $body->rewind();

        return $body;
    }

    /**
     * @param mixed $data
     */
    private function setPayload(string $data): void
    {
        $this->payload = $data;
    }

    /**
     * Update the response body for the given instance.
     *
     * @param self $toUpdate Instance to update.
     * @return JsonStringResponse Returns a new instance with an updated body.
     */
    private function updateBodyFor(JsonStringResponse $toUpdate): JsonStringResponse
    {
        $body = $this->createBodyFromJson($toUpdate->getPayload());
        return $toUpdate->withBody($body);
    }
}
