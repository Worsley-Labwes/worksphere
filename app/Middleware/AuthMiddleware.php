<?php

namespace App\Middleware;

class AuthMiddleware
{
    public function handle(): void
    {
        if (!auth_user()) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['intended'] = $_SERVER['REQUEST_URI'];
            }
            redirect('/login');
        }
    }
}
