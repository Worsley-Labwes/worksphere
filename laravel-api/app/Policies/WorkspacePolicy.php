<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

class WorkspacePolicy
{
    // Non-members get a 404, not a 403, so other tenants' workspaces are never revealed.
    public function view(User $user, Workspace $workspace): Response
    {
        return $workspace->roleFor($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
