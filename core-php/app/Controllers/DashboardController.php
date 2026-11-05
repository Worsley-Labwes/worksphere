<?php

namespace App\Controllers;

class DashboardController
{
    public function index(): void
    {
        view('dashboard/index', ['user' => auth_user()]);
    }
}
