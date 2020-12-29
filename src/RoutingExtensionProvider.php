<?php


namespace Atom\Web;

use Atom\Routing\Router;
use Atom\Web\Contracts\RendererExtensionProvider;

class RoutingExtensionProvider implements RendererExtensionProvider
{
    /**
     * @var Router
     */
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function getExtensions(): array
    {
        return [
            "route" => [$this->router, "pathFor"],
            "asset" => [$this->router, "asset"]
        ];
    }
}
