<?php

namespace App\Controllers;

use Config\Database;

class HomeController
{
    public function index(): void
    {
        $dbOk = false;
        try {
            Database::connect()->query('SELECT 1');
            $dbOk = true;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }
        require __DIR__ . '/../../views/home.php';
    }
}
