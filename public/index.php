<?php

declare(strict_types=1);

use App\Support\Env;
use App\Http\Request;
use App\Http\Kernel;
use App\Http\Router;

require __DIR__ . '/../vendor/autoload.php';

Env::load(dirname(__DIR__));

$app = require __DIR__ . '/../config/app.php';
if (($app['debug'] ?? false) === true) {
   ini_set('display_errors', '1');
   error_reporting(E_ALL);
} else {
   ini_set('display_errors', '0');
}

$router = new Router();
require __DIR__ . '/../routes/web.php';  // si usas vistas
require __DIR__ . '/../routes/api.php';

$kernel = new Kernel();
$kernel->handle(Request::capture(), $router);
