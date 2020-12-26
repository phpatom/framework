<?php


namespace Atom\Web\Providers\DebugBar\Collectors;

use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\Renderable;
use Psr\Http\Server\MiddlewareInterface;

class MiddlewareCollector extends MessagesCollector implements Renderable
{

    private $middlewares = [];
    private $index = 1;

    public function addMiddleware(MiddlewareInterface $middleware)
    {
        $this->middlewares[get_class($middleware)] = "$this->index -" . get_class($middleware);
        $this->index++;
    }

    public function collect(): array
    {
        foreach ($this->middlewares as $index => $middleware) {
            $this->addMessage($middleware, $index);
        }
        return [
            'count' => count($this->middlewares),
            'messages' => $this->getMessages(),
        ];
    }

    public function getName(): string
    {
        return "Middlewares";
    }

    public function getWidgets(): array
    {
        $widgets = parent::getWidgets();
        $widgets['models']['icon'] = 'cubes';
        return $widgets;
    }

}