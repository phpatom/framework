<?php


namespace Atom\Web\Providers\DebugBar\Collectors;


use DebugBar\DataCollector\DataCollectorInterface;

class RouteCollector implements DataCollectorInterface
{

    function collect(): array
    {
        return ["LOLE"=>"BLABLA"];
    }

    /**
     * Returns the unique name of the collector
     *
     * @return string
     */
    function getName(): string
    {
        return "Routes";
    }
}