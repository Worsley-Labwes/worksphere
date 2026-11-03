<?php

use App\Controllers\{HomeController, AuthController, DashboardController, WorkspaceController, InvitationController};
use App\Middleware\{AuthMiddleware, GuestMiddleware};

$router->get('/', [HomeController::class, 'index']);

// Authentication
$router->get('/register', [AuthController::class, 'showRegister'], [GuestMiddleware::class]);
$router->post('/register', [AuthController::class, 'register'], [GuestMiddleware::class]);
$router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
$router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class]);
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

// Dashboard
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

// Workspaces
$router->get('/workspaces', [WorkspaceController::class, 'index'], [AuthMiddleware::class]);
$router->get('/workspaces/create', [WorkspaceController::class, 'create'], [AuthMiddleware::class]); // must come BEFORE {id}
$router->post('/workspaces', [WorkspaceController::class, 'store'], [AuthMiddleware::class]);
$router->get('/workspaces/{id}', [WorkspaceController::class, 'show'], [AuthMiddleware::class]);

// Invitations
$router->post('/workspaces/{id}/invitations', [InvitationController::class, 'store'], [AuthMiddleware::class]);
$router->get('/invitations/{token}', [InvitationController::class, 'show'], [AuthMiddleware::class]);
$router->post('/invitations/{token}/accept', [InvitationController::class, 'accept'], [AuthMiddleware::class]);
$router->post('/invitations/{token}/decline', [InvitationController::class, 'decline'], [AuthMiddleware::class]);
