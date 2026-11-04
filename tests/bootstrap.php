<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/database.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();

// Tests must NEVER touch the real database.
$_ENV['DB_NAME'] = 'worksphere_test';
