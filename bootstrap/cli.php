<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';

if (class_exists(\Dotenv\Dotenv::class) && file_exists($root . '/.env')) {
   $dotenv = \Dotenv\Dotenv::createImmutable($root);
   $dotenv->load(); // <- esto rellena $_ENV/$_SERVER
}

date_default_timezone_set('America/Mexico_City');
