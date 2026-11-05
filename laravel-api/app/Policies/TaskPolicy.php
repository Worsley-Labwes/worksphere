<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    // Moving a task between columns: its assignee, its creator, or a manager.
    public function update(User $user, Task $task): Response
    {
        $role = $task->workspace->roleFor($user);

        if (! $role) {
            return Response::denyAsNotFound();
        }

        $allowed = in_array($role, ['owner', 'admin'], true)
            || (int) $task->assigned_to === $user->id
            || (int) $task->created_by === $user->id;

        return $allowed
            ? Response::allow()
            : Response::deny('Only the assignee, the creator, or an admin can move this task.');
    }

    // Deleting a task: its creator or a manager.
    public function delete(User $user, Task $task): Response
    {
        $role = $task->workspace->roleFor($user);

        if (! $role) {
            return Response::denyAsNotFound();
        }

        $allowed = in_array($role, ['owner', 'admin'], true)
            || (int) $task->created_by === $user->id;

        return $allowed
            ? Response::allow()
            : Response::deny('Only the creator or an admin can delete this task.');
    }
}
