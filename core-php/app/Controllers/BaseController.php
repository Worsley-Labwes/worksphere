<?php

namespace App\Controllers;

use App\Models\Workspace;

abstract class BaseController
{
    // Returns the workspace only if the logged-in user is a member; otherwise 404.
    protected function workspace(string $id): array
    {
        $workspace = Workspace::findForUser((int) $id, auth_user()['id']);
        if (!$workspace) {
            abort(404);
        }
        return $workspace;
    }

    protected function isManager(array $workspace): bool
    {
        return in_array($workspace['role'], ['owner', 'admin'], true);
    }

    protected function requireManager(array $workspace): void
    {
        if (!$this->isManager($workspace)) {
            abort(403, 'Only owners and admins can do that.');
        }
    }
}
