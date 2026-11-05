<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InvitationPolicy
{
    // Accept or decline: the invitation must be pending and addressed to this user.
    // Anything else is a 404, so a stolen or stale token reveals nothing.
    public function respond(User $user, Invitation $invitation): Response
    {
        return $invitation->isPending() && strtolower($invitation->email) === strtolower($user->email)
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
