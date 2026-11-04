<?php

use App\Controllers\{HomeController, AuthController, DashboardController, WorkspaceController, InvitationController, ProjectController, TaskController};
use App\Middleware\{AuthMiddleware, GuestMiddleware};

$auth = [AuthMiddleware::class];
$guest = [GuestMiddleware::class];

$router->get('/', [HomeController::class, 'index']);

// Authentication
$router->get('/register', [AuthController::class, 'showRegister'], $guest);
$router->post('/register', [AuthController::class, 'register'], $guest);
$router->get('/login', [AuthController::class, 'showLogin'], $guest);
$router->post('/login', [AuthController::class, 'login'], $guest);
$router->post('/logout', [AuthController::class, 'logout'], $auth);

// Dashboard
$router->get('/dashboard', [DashboardController::class, 'index'], $auth);

// Workspaces
$router->get('/workspaces', [WorkspaceController::class, 'index'], $auth);
$router->get('/workspaces/create', [WorkspaceController::class, 'create'], $auth); // before {id}
$router->post('/workspaces', [WorkspaceController::class, 'store'], $auth);
$router->get('/workspaces/{id}', [WorkspaceController::class, 'show'], $auth);

// Invitations
$router->post('/workspaces/{id}/invitations', [InvitationController::class, 'store'], $auth);
$router->get('/invitations/{token}', [InvitationController::class, 'show'], $auth);
$router->post('/invitations/{token}/accept', [InvitationController::class, 'accept'], $auth);
$router->post('/invitations/{token}/decline', [InvitationController::class, 'decline'], $auth);

// Projects
$router->get('/workspaces/{id}/projects', [ProjectController::class, 'index'], $auth);
$router->get('/workspaces/{id}/projects/create', [ProjectController::class, 'create'], $auth); // before {projectId}
$router->post('/workspaces/{id}/projects', [ProjectController::class, 'store'], $auth);
$router->get('/workspaces/{id}/projects/{projectId}', [ProjectController::class, 'show'], $auth);
$router->get('/workspaces/{id}/projects/{projectId}/edit', [ProjectController::class, 'edit'], $auth);
$router->post('/workspaces/{id}/projects/{projectId}/update', [ProjectController::class, 'update'], $auth);

// Tasks
$router->post('/workspaces/{id}/projects/{projectId}/tasks', [TaskController::class, 'store'], $auth);
$router->post('/workspaces/{id}/tasks/{taskId}/status', [TaskController::class, 'updateStatus'], $auth);
$router->post('/workspaces/{id}/tasks/{taskId}/delete', [TaskController::class, 'delete'], $auth);
