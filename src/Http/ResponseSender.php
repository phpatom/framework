<?php


namespace Atom\Framework\Http;

use Atom\Routing\Contracts\RouterContract;
use Laminas\Diactoros\Response as BaseResponse;
use Psr\Http\Message\ResponseInterface;

class ResponseSender
{
    /**
     * @var RouterContract
     */
    private RouterContract $router;

    public function __construct(RouterContract $router)
    {
        $this->router = $router;
    }

    public function json(
        $data,
        int $status = 200,
        array $headers = [],
        int $encodingOptions = BaseResponse\JsonResponse::DEFAULT_JSON_FLAGS
    ): BaseResponse\JsonResponse {
        return Response::json($data, $status, $headers, $encodingOptions);
    }

    public function send(
        $data = null,
        int $status = 200
    ): ResponseInterface {
        if (is_null($data)) {
            return $this->empty($status);
        }
        if (is_array($data)) {
            return Response::json($data, $status);
        }
        return Response::html($data, $status);
    }

    public function jsonString(
        string $data,
        int $status = 200,
        array $headers = [],
        int $encodingOptions = BaseResponse\JsonResponse::DEFAULT_JSON_FLAGS
    ): JsonStringResponse {
        return Response::jsonString($data, $status, $headers, $encodingOptions);
    }

    public function html(
        $data,
        int $status = 200,
        array $headers = []
    ): BaseResponse\HtmlResponse {
        return Response::html($data, $status, $headers);
    }

    public function text(
        $data,
        int $status = 200,
        array $headers = []
    ): BaseResponse\TextResponse {
        return Response::text($data, $status, $headers);
    }

    public function empty(
        int $status = 200,
        array $headers = []
    ): BaseResponse\EmptyResponse {
        return Response::empty($status, $headers);
    }

    public function redirect(
        $uri,
        int $status = 200,
        array $headers = []
    ): BaseResponse\RedirectResponse {
        return Response::redirect($uri, $status, $headers);
    }

    /**
     * @param $uri
     * @param array $data
     * @param int $status
     * @param array $headers
     * @return BaseResponse\RedirectResponse
     */
    public function redirectRoute(
        $uri,
        array $data = [],
        int $status = 200,
        array $headers = []
    ): BaseResponse\RedirectResponse {
        return $this->redirect($this->router->generateUrl($uri, $data), $status, $headers);
    }
}
