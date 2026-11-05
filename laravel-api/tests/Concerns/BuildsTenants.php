<?php

namespace Tests\Concerns;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Arr;

trait BuildsTenants
{
    protected function createWorkspace(User $owner, string $name = 'Company A'): Workspace
    {
        $workspace = Workspace::create(['name' => $name, 'owner_id' => $owner->id]);
        $workspace->members()->attach($owner->id, ['role' => 'owner']);

        return $workspace;
    }

    protected function addMember(Workspace $workspace, User $user, string $role = 'member'): void
    {
        $workspace->members()->attach($user->id, ['role' => $role]);
    }

    protected function createProject(Workspace $workspace, User $creator, array $attributes = []): Project
    {
        $project = new Project($attributes + ['name' => 'Website redesign', 'status' => 'active']);
        $project->workspace_id = $workspace->id;
        $project->created_by = $creator->id;
        $project->save();

        return $project;
    }

    protected function createTask(Project $project, User $creator, array $attributes = []): Task
    {
        $task = new Task(Arr::except($attributes, ['status']) + ['title' => 'Write copy', 'priority' => 'medium']);
        $task->workspace_id = $project->workspace_id;
        $task->project_id = $project->id;
        $task->created_by = $creator->id;
        $task->status = $attributes['status'] ?? 'todo';
        $task->save();

        return $task;
    }
}
