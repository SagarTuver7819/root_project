<?php

use App\Core\App;
use App\Core\Request;
use App\Core\Router;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

App::bootstrap();

$router = new Router();
require BASE_PATH . '/routes/web.php';

$request = new Request();
$router->dispatch($request);
