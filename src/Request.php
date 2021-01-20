<?php


namespace Atom\Framework;

use Atom\Routing\MatchedRoute;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Negotiation\Accept;
use Negotiation\Negotiator;
use Psr\Http\Message\ServerRequestInterface;

class Request extends ServerRequest
{
    use Format;

    /**
     * @var Accept[]
     */
    private $acceptedContentTypes;

    public static function incoming(): Request
    {
        return self::convert(ServerRequestFactory::fromGlobals());
    }

    public static function convert(ServerRequestInterface $request): Request
    {
        $new = new self(
            $request->getServerParams(),
            $request->getUploadedFiles(),
            $request->getUri(),
            $request->getMethod(),
            $request->getBody(),
            $request->getHeaders(),
            $request->getCookieParams(),
            $request->getQueryParams(),
            $request->getBody(),
            $request->getProtocolVersion()
        );
        foreach ($request->getAttributes() as $k => $v) {
            $new = $new->withAttribute($k, $v);
        }
        return $new;
    }

    public function route(): ?MatchedRoute
    {
        return MatchedRoute::of($this);
    }

    public function isJson(): bool
    {
        $contentType = $this->getHeaderLine("content-type");
        return str_contains($contentType, '/json') || str_contains($contentType, '+json');
    }

    /**
     * Determine if the current request probably expects a JSON response.
     *
     * @return bool
     */
    public function expectsJson(): bool
    {
        return ($this->isAjax() && !$this->isPjax() && $this->acceptsAnyContentType()) || $this->wantsJson();
    }

    /**
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * @return bool
     */
    public function isPjax(): bool
    {
        return $this->hasHeader('X-PJAX');
    }


    /**
     * @return bool
     */
    public function wantsJson(): bool
    {
        $acceptable = $this->getAcceptableContentTypes();
        if (empty($acceptable)) {
            return false;
        }
        $type = $acceptable[0]->getType();
        return str_contains($type, '/json') || str_contains($type, '+json');
    }

    /**
     * @param string $contentType
     * @return bool
     */
    public function accepts(string $contentType): bool
    {
        $accepts = $this->getAcceptableContentTypes();
        if (count($accepts) === 0) {
            return true;
        }
        foreach ($accepts as $accept) {
            $type = $accept->getType();
            if ($type === '*/*' || $type === '*') {
                return true;
            }
            if ($type === $contentType) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if the current request accepts any content type.
     *
     * @return bool
     */
    public function acceptsAnyContentType(): bool
    {
        $acceptable = $this->getAcceptableContentTypes();

        return count($acceptable) === 0 || (
                isset($acceptable[0]) && ($acceptable[0]->getType() === '*/*' || $acceptable[0]->getType() === '*')
            );
    }

    /**
     * Determines whether a request accepts JSON.
     *
     * @return bool
     */
    public function acceptsJson(): bool
    {
        return $this->accepts('application/json');
    }

    /**
     * Determines whether a request accepts HTML.
     *
     * @return bool
     */
    public function acceptsHtml(): bool
    {
        return $this->accepts('text/html');
    }

    /**
     * Get the data format expected in the response.
     *
     * @param string $default
     * @return string
     */
    public function format($default = 'html'): string
    {
        foreach ($this->getAcceptableContentTypes() as $type) {
            if ($typeFormat = $this->getFormat($type->getType())) {
                return $typeFormat;
            }
        }
        return $default;
    }

    /**
     * @return Accept[]
     */
    private function getAcceptableContentTypes(): array
    {
        if (is_null($this->acceptedContentTypes)) {
            $negotiator = new Negotiator();
            $acceptHeader = $this->getHeaderLine("Accept");
            $this->acceptedContentTypes = $negotiator->getOrderedElements($acceptHeader) ?? [];
        }
        return $this->acceptedContentTypes;
    }
}
