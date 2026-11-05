<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class WorkspaceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return WorkspaceResource::collection(
            $request->user()->workspaces()->latest('workspaces.created_at')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        // Workspace and owner membership are created together or not at all.
        $workspace = DB::transaction(function () use ($request, $data) {
            $workspace = Workspace::create([
                'name'     => $data['name'],
                'owner_id' => $request->user()->id,
            ]);
            $workspace->members()->attach($request->user()->id, ['role' => 'owner']);

            return $workspace;
        });

        return (new WorkspaceResource($workspace))->response()->setStatusCode(201);
    }

    public function show(Workspace $workspace): WorkspaceResource
    {
        Gate::authorize('view', $workspace);

        return new WorkspaceResource($workspace);
    }
}
