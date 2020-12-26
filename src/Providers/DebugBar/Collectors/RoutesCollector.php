<?php


namespace Atom\Web\Providers\DebugBar\Collectors;


use Atom\Routing\Router;
use DebugBar\DataCollector\MessagesCollector;

class RoutesCollector extends MessagesCollector
{
    /**
     * @var Router
     */
    private $router;

    public function __construct(Router $router)
    {
        parent::__construct("routes");
        $this->router = $router;
    }

    public function collect(): array
    {
        $count = 0;
        foreach ($this->router->getRoutes() as $route) {
            $handlerName = $this->getNameFromHandler($route->getHandler());
            $methods = implode(",", $route->getMethods());
            $count++;
            $this->addMessage($route->getName() . " ({$methods})" . "  ~  \" {$route->getPattern()}\"", $handlerName . " \"");
        }

        foreach ($this->router->getRouteGroups() as $routeGroups) {
            foreach ($routeGroups->getRoutes() as $route) {
                $handlerName = $this->getNameFromHandler($route->getHandler());
                $methods = implode(",", $route->getMethods());
                $url = $routeGroups->getPattern() . $route->getPattern();
                $count++;
                $this->addMessage($route->getName() . " ({$methods})" . "  ~  \" $url", $handlerName . " \"");
            }
        }
        return [
            'count' => $count,
            'messages' => $this->getMessages(),
        ];
    }

    function getName(): string
    {
        return "Routes";
    }

    public function getWidgets(): array
    {
        $widgets = parent::getWidgets();
        $widgets['models']['icon'] = 'cubes';
        return $widgets;
    }

    public function getNameFromHandler($handler)
    {
        if (is_string($handler)) {
            return $handler;
        }
        if (is_array($handler) && (count($handler) >= 2)) {
            return $handler[0] . "@" . $handler[1];
        }
        if (is_object($handler)) {
            return get_class($handler);
        }
        return "Callable";
    }

}