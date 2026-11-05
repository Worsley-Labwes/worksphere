<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

class WorkspacePolicy
{
    // Any member. Non-members get a 404, not a 403, so other tenants' workspaces are never revealed.
    public function view(User $user, Workspace $workspace): Response
    {
        return $workspace->roleFor($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    // Owners and admins.
    public function manage(User $user, Workspace $workspace): Response
    {
        $role = $workspace->roleFor($user);

        if (! $role) {
            return Response::denyAsNotFound();
        }

        return in_array($role, ['owner', 'admin'], true)
            ? Response::allow()
            : Response::deny('Only owners and admins can do that.');
    }

    // Owners can invite anyone; admins can only invite members.
    public function invite(User $user, Workspace $workspace, string $role = 'member'): Response
    {
        $current = $workspace->roleFor($user);

        if (! $current) {
            return Response::denyAsNotFound();
        }
        if ($current === 'owner' || ($current === 'admin' && $role === 'member')) {
            return Response::allow();
        }

        return Response::deny(
            $current === 'admin' ? 'Only the owner can invite admins.' : 'Only owners and admins can invite members.'
        );
    }
}
