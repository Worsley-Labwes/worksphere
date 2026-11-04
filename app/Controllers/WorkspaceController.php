<?php

namespace App\Controllers;

use App\Models\{ActivityLog, Invitation, Stats, Workspace};

class WorkspaceController
{
    public function index(): void
    {
        $user = auth_user();

        view('workspaces/index', [
            'workspaces'  => Workspace::forUser($user['id']),
            'invitations' => Invitation::pendingForEmail($user['email']),
        ]);
    }

    public function create(): void
    {
        view('workspaces/create', ['errors' => pull('errors'), 'old' => pull('old')]);
    }

    public function store(): void
    {
        $name = trim($_POST['name'] ?? '');

        if ($name === '' || mb_strlen($name) > 100) {
            $_SESSION['errors'] = ['name' => 'Workspace name is required (max 100 characters).'];
            $_SESSION['old'] = ['name' => $name];
            redirect('/workspaces/create');
        }

        $id = Workspace::create($name, auth_user()['id']);
        ActivityLog::record($id, auth_user()['id'], 'workspace.created', 'created the workspace');
        redirect("/workspaces/$id");
    }

    public function show(string $id): void
    {
        $user = auth_user();
        $workspace = Workspace::findForUser((int) $id, $user['id']);

        // 404, not 403: don't reveal that someone else's workspace exists.
        if (!$workspace) {
            abort(404);
        }

        $canInvite = in_array($workspace['role'], ['owner', 'admin'], true);

        view('workspaces/show', [
            'workspace'   => $workspace,
            'members'     => Workspace::members((int) $id),
            'canInvite'   => $canInvite,
            'pending'     => $canInvite ? Invitation::pendingForWorkspace((int) $id) : [],
            'inviteLink'  => pull('invite_link', null),
            'inviteError' => pull('invite_error', null),
            'stats'       => Stats::forWorkspace((int) $id, $user['id']),
            'activity'    => ActivityLog::recent((int) $id),
        ]);
    }
}
