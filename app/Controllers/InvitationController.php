<?php

namespace App\Controllers;

use App\Models\{ActivityLog, Invitation, Workspace};

class InvitationController
{
    public function store(string $workspaceId): void
    {
        $user = auth_user();
        $workspace = Workspace::findForUser((int) $workspaceId, $user['id']);

        if (!$workspace) {
            abort(404);
        }
        if (!in_array($workspace['role'], ['owner', 'admin'], true)) {
            abort(403, 'Only owners and admins can invite members.');
        }

        $email = strtolower(trim($_POST['email'] ?? ''));
        $role = $_POST['role'] ?? 'member';
        $error = null;

        if (!in_array($role, ['admin', 'member'], true)) {
            $error = 'Invalid role.';
        } elseif ($role === 'admin' && $workspace['role'] !== 'owner') {
            $error = 'Only the owner can invite admins.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } elseif (Workspace::hasMemberWithEmail((int) $workspaceId, $email)) {
            $error = 'That person is already a member.';
        } elseif (Invitation::existsPending((int) $workspaceId, $email)) {
            $error = 'There is already a pending invitation for that email.';
        }

        if ($error) {
            $_SESSION['invite_error'] = $error;
            redirect("/workspaces/$workspaceId");
        }

        $token = Invitation::create((int) $workspaceId, $email, $role, $user['id']);
        ActivityLog::record((int) $workspaceId, $user['id'], 'invitation.sent', "invited $email as $role");

        $_SESSION['invite_link'] = "http://{$_SERVER['HTTP_HOST']}/invitations/$token";
        redirect("/workspaces/$workspaceId");
    }

    public function show(string $token): void
    {
        view('invitations/show', ['invitation' => $this->load($token), 'token' => $token]);
    }

    public function accept(string $token): void
    {
        $invitation = $this->load($token);
        Invitation::accept($invitation, auth_user()['id']);
        ActivityLog::record(
            (int) $invitation['workspace_id'],
            auth_user()['id'],
            'member.joined',
            "joined the workspace as {$invitation['role']}"
        );
        redirect('/workspaces/' . (int) $invitation['workspace_id']);
    }

    public function decline(string $token): void
    {
        $invitation = $this->load($token);
        Invitation::decline((int) $invitation['id']);
        redirect('/workspaces');
    }

    // Shared checks: the invitation must exist, be pending, and be addressed to THIS user.
    private function load(string $token): array
    {
        $invitation = Invitation::findPending($token);

        if (!$invitation) {
            abort(404, 'Invitation not found or expired.');
        }
        if (strtolower($invitation['email']) !== strtolower(auth_user()['email'])) {
            abort(403, 'This invitation was sent to a different email address.');
        }
        return $invitation;
    }
}
