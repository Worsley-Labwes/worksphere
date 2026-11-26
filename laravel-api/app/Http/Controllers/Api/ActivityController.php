<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\Workspace;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ActivityController extends Controller
{
    /**
     * Recent activity in a workspace, newest first (20 per page).
     */
    public function index(Workspace $workspace): AnonymousResourceCollection
    {
        Gate::authorize('view', $workspace);

        return ActivityLogResource::collection(
            ActivityLog::where('workspace_id', $workspace->id)
                ->with('user')
                ->orderByDesc('id')
                ->paginate(20)
        );
    }
}
