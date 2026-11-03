<?php

namespace App\Middleware;

class GuestMiddleware
{
    public function handle(): void
    {
        if (auth_user()) {
            redirect('/dashboard');
        }
    }
}
