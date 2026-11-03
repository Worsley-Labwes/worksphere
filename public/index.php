<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/database.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

$router = new App\Router();
require __DIR__ . '/../routes/web.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
