<?php


namespace Atom\Web\Exceptions;

use Atom\Routing\Contracts\RouteContract;
use Atom\Routing\MatchedRoute;
use Throwable;

class InvalidRouteHandlerException extends \Exception
{
    public function __construct(MatchedRoute $match)
    {
        parent::__construct("The handler provided for the route {$match->getRoute()->getName()} is not valid");
    }
}
