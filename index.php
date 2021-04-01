<?php

use Atom\Framework\Http\Response;

require_once "vendor/autoload.php";
ini_set('display_errors', 1);

$app = atom(__DIR__);
$app->get("/hello/{name}", function (string $name, $res) {
    return $res->json(["foo" => $name], 200);
});
$app->post('/create', function (Response $response) {
    return $response->json(["message" => "created"])
        ->withStatus(201);
});
$app->run();
