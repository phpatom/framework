<?php

use Atom\Routing\MatchedRoute;
use Atom\Routing\Router;
use Atom\Validation\Validator;
use Atom\Web\Providers\DebugBar\DebugBar;
use Atom\Web\Validation;
use Atom\Web\WebApp;
use Psr\Http\Message\ServerRequestInterface;

require_once "vendor/autoload.php";

$app = WebApp::create(__DIR__)
    ->use(new Validation())
    ->use(new DebugBar())
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
            return <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
             <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
                         <meta http-equiv="X-UA-Compatible" content="ie=edge">
             <title>Document</title>
</head>
<body>
  
</body>
</html>
HTML;
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
