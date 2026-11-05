<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\Invitation;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class InvitationController extends Controller
{
    // Pending invitations addressed to the logged-in user.
    public function mine(Request $request): AnonymousResourceCollection
    {
        return InvitationResource::collection(
            Invitation::pending()
                ->where('email', strtolower($request->user()->email))
                ->with('workspace')
                ->latest()
                ->get()
        );
    }

    // Pending invitations for a workspace (owners and admins).
    public function index(Workspace $workspace): AnonymousResourceCollection
    {
        Gate::authorize('manage', $workspace);

        return InvitationResource::collection($workspace->invitations()->pending()->latest()->get());
    }

    public function store(StoreInvitationRequest $request, Workspace $workspace): JsonResponse
    {
        $invitation = new Invitation($request->validated() + ['role' => 'member']);
        $invitation->workspace_id = $workspace->id;
        $invitation->invited_by = $request->user()->id;
        $invitation->token = bin2hex(random_bytes(32));
        $invitation->status = 'pending';
        $invitation->expires_at = now()->addDays(7);
        $invitation->save();

        return (new InvitationResource($invitation))->response()->setStatusCode(201);
    }

    public function accept(Request $request, Invitation $invitation): WorkspaceResource
    {
        Gate::authorize('respond', $invitation);

        $workspace = $invitation->workspace;

        DB::transaction(function () use ($request, $invitation, $workspace) {
            // Never overwrite the role of someone who is already a member.
            if (! $workspace->members()->where('users.id', $request->user()->id)->exists()) {
                $workspace->members()->attach($request->user()->id, ['role' => $invitation->role]);
            }

            $invitation->status = 'accepted';
            $invitation->save();
        });

        return new WorkspaceResource($workspace);
    }

    public function decline(Invitation $invitation): Response
    {
        Gate::authorize('respond', $invitation);

        $invitation->status = 'declined';
        $invitation->save();

        return response()->noContent();
    }
}
