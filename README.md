# Basic usage
```php
<?php

use Atom\Routing\MatchedRoute;
use Atom\Routing\Router;
use Atom\Web\WebApp;
use Psr\Http\Message\ServerRequestInterface;

require_once "vendor/autoload.php";

$app = WebApp::create(__DIR__)
    ->get("/hello/{name}", function ($name) {
        return ["hello" => $name];
    }, "say.hello");
$app->run();

```

