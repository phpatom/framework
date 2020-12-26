<?php


namespace Atom\Web;


use Atom\Routing\MatchedRoute;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

class Request extends ServerRequest
{

    public static function incoming(): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals();
    }

    public function route(): ?MatchedRoute
    {
        return MatchedRoute::of($this);
    }

}