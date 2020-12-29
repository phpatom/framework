<?php


namespace Atom\Web;

use Atom\Routing\Exceptions\RouteNotFoundException;
use Atom\Routing\Router;
use Laminas\Diactoros\Response as BaseResponse;

class Response extends BaseResponse
{
    public static function json(
        $data,
        int $status = 200,
        array $headers = [],
        int $encodingOptions = BaseResponse\JsonResponse::DEFAULT_JSON_FLAGS
    ): BaseResponse\JsonResponse {
        return new BaseResponse\JsonResponse($data, $status, $headers, $encodingOptions);
    }

    public static function jsonString(
        string $data,
        int $status = 200,
        array $headers = [],
        int $encodingOptions = BaseResponse\JsonResponse::DEFAULT_JSON_FLAGS
    ): JsonStringResponse {
        return new JsonStringResponse($data, $status, $headers, $encodingOptions);
    }

    public static function html(
        $data,
        int $status = 200,
        array $headers = []
    ): BaseResponse\HtmlResponse {
        return new BaseResponse\HtmlResponse($data, $status, $headers);
    }

    public static function text(
        $data,
        int $status = 200,
        array $headers = []
    ): BaseResponse\TextResponse {
        return new BaseResponse\TextResponse($data, $status, $headers);
    }

    public static function empty(
        int $status = 200,
        array $headers = []
    ): BaseResponse\EmptyResponse {
        return new BaseResponse\EmptyResponse($status, $headers);
    }

    public static function redirect(
        $uri,
        int $status = 200,
        array $headers = []
    ): BaseResponse\RedirectResponse {
        return new BaseResponse\RedirectResponse($uri, $status, $headers);
    }

    /**
     * @param $uri
     * @param array $data
     * @param int $status
     * @param array $headers
     * @return BaseResponse\RedirectResponse
     * @throws RouteNotFoundException
     */
    public static function redirectRoute(
        $uri,
        array $data = [],
        int $status = 200,
        array $headers = []
    ): BaseResponse\RedirectResponse {
        return self::redirect(Router::$instance->generateUrl($uri, $data), $status, $headers);
    }
}
