<?php


namespace Atom\Web\Providers\DebugBar;


use Atom\DI\Exceptions\CircularDependencyException;
use Atom\DI\Exceptions\ContainerException;
use Atom\DI\Exceptions\NotFoundException;
use Atom\DI\Exceptions\StorageNotFoundException;
use Atom\Web\Providers\DebugBar\Collectors\RoutesCollector;
use DebugBar\DebugBarException;
use Laminas\Diactoros\StreamFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DebugBarMiddleware implements MiddlewareInterface
{

    /**
     * @var AtomDebugBar
     */
    private $debugBar;

    public function __construct(AtomDebugBar $debugBar)
    {

        $this->debugBar = $debugBar;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws DebugBarException
     * @throws CircularDependencyException
     * @throws ContainerException
     * @throws NotFoundException
     * @throws StorageNotFoundException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $this->debugBar->addCollector($handler->container()->get(RoutesCollector::class));
        $body = (string)$response->getBody();
        $urlString = $this->buildAssetsUrl();
        $result = str_replace("</body>", $urlString . "</body>", $body);
        $factory = new StreamFactory();
        $response = $response->withBody($factory->createStream($result));
        return $response;
    }

    private function buildAssetsUrl(): string
    {
        $cssAssetUrl = DebugBarAssetMiddleware::CSS_URL;
        $jsAssetUrl = DebugBarAssetMiddleware::JS_URL;
        $cssString = "<link type='text/css' rel='stylesheet' href='$cssAssetUrl'>";
        $JsString = "<script type='application/javascript' src='$jsAssetUrl'></script>";
        return $cssString . $JsString . $this->debugBar->getJavascriptRenderer()->render();
    }
}