<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(Workspace $workspace): AnonymousResourceCollection
    {
        Gate::authorize('view', $workspace);

        return ProjectResource::collection(
            $workspace->projects()
                ->withProgress()
                ->orderByRaw("status = 'archived'")
                ->latest()
                ->get()
        );
    }

    public function store(ProjectRequest $request, Workspace $workspace): JsonResponse
    {
        $project = new Project($request->validated() + ['status' => 'planning']);
        $project->workspace_id = $workspace->id;
        $project->created_by = $request->user()->id;
        $project->save();

        return (new ProjectResource($project->loadProgress()))->response()->setStatusCode(201);
    }

    public function show(Workspace $workspace, Project $project): ProjectResource
    {
        Gate::authorize('view', $workspace);

        $project->loadProgress()->load([
            'tasks' => fn($query) => $query
                ->with('assignee')
                ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
                ->orderBy('id'),
        ]);

        return new ProjectResource($project);
    }

    public function update(ProjectRequest $request, Workspace $workspace, Project $project): ProjectResource
    {
        $project->update($request->validated());

        return new ProjectResource($project->loadProgress());
    }
}
