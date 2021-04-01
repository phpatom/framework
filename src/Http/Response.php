<?php


namespace Atom\Framework\Http;

use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\Event\Exceptions\ListenerAlreadyAttachedToEvent;
use Atom\Framework\Application;
use Atom\Framework\Contracts\HasKernel;
use Atom\Routing\Exceptions\RouteNotFoundException;
use Laminas\Diactoros\Response as BaseResponse;
use ReflectionException;
use Throwable;

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
     * @param HasKernel $kernel
     * @param $uri
     * @param array $data
     * @param int $status
     * @param array $headers
     * @return BaseResponse\RedirectResponse
     * @throws RouteNotFoundException
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ListenerAlreadyAttachedToEvent
     * @throws ReflectionException
     * @throws Throwable
     */
    public static function redirectRoute(
        HasKernel $kernel,
        $uri,
        array $data = [],
        int $status = 200,
        array $headers = []
    ): BaseResponse\RedirectResponse {
        return self::redirect(Application::of($kernel)->router()->generateUrl($uri, $data), $status, $headers);
    }
}
