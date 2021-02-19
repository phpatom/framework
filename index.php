<?php
require_once "vendor/autoload.php";

$app = atom(__DIR__);
$app
    ->get("/hello/{name}", fn($name) => ["hello" => $name])
    ->run();
