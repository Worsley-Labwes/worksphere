<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function store(StoreTaskRequest $request, Workspace $workspace, Project $project): JsonResponse
    {
        if (in_array($project->status, ['completed', 'archived'], true)) {
            throw ValidationException::withMessages([
                'project' => ['Tasks cannot be added to a completed or archived project.'],
            ]);
        }

        $task = new Task($request->validated() + ['priority' => 'medium']);
        $task->workspace_id = $workspace->id;
        $task->project_id = $project->id;
        $task->created_by = $request->user()->id;
        $task->status = 'todo';
        $task->save();

        return (new TaskResource($task->load('assignee')))->response()->setStatusCode(201);
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Workspace $workspace, Task $task): TaskResource
    {
        $task->status = $request->validated('status');
        $task->save();

        return new TaskResource($task->load('assignee'));
    }

    public function destroy(Workspace $workspace, Task $task): Response
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return response()->noContent();
    }
}
