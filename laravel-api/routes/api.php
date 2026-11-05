<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('workspaces', WorkspaceController::class)->only(['index', 'store', 'show']);

    // Invitations addressed to the logged-in user (the token is the key).
    Route::get('/invitations', [InvitationController::class, 'mine']);
    Route::post('/invitations/{invitation:token}/accept', [InvitationController::class, 'accept']);
    Route::post('/invitations/{invitation:token}/decline', [InvitationController::class, 'decline']);

    // Scoped bindings: a child ID only resolves if it belongs to the parent in the URL.
    Route::scopeBindings()->group(function () {
        Route::get('/workspaces/{workspace}/invitations', [InvitationController::class, 'index']);
        Route::post('/workspaces/{workspace}/invitations', [InvitationController::class, 'store']);

        Route::apiResource('workspaces.projects', ProjectController::class)->except('destroy');

        Route::post('/workspaces/{workspace}/projects/{project}/tasks', [TaskController::class, 'store']);
        Route::patch('/workspaces/{workspace}/tasks/{task}/status', [TaskController::class, 'updateStatus']);
        Route::delete('/workspaces/{workspace}/tasks/{task}', [TaskController::class, 'destroy']);
    });
});
