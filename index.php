<?php

use Atom\Routing\MatchedRoute;
use Atom\Routing\Router;
use Atom\Validation\Validator;
use Atom\Web\Validation;
use Atom\Web\WebApp;
use Psr\Http\Message\ServerRequestInterface;

require_once "vendor/autoload.php";

$app = WebApp::create(__DIR__)
    ->use(new Validation())
    ->get("/hello/{name}", function ($name) {
        return ["hello" => $name];
    }, "hello")
    ->get("/", function (
        MatchedRoute $matchedRoute,
        Router $router,
        ServerRequestInterface $request,
        Validator $validator
    ) {
        $validator->assert("name")
            ->onQueryParams()->is()
            ->present()->filled()->and()
            ->alphabetic();
        $validator->check($request);
        if ($validator->failed()) {
            return ["errors" => $validator->errors()];
        }
        return [
            "Atom" => "Welcome",
            "path" => $matchedRoute->getPath(),
            "method" => $matchedRoute->getMethod(),
            "route" => $matchedRoute->getRoute()->getName(),
            "hello" => $router->generateUrl("hello", ["name" => $request->getQueryParams()["name"]])
        ];
    }, "home");
$app->run();
