<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            // Uses the pivot when listing (no extra query), otherwise looks the role up.
            'role' => $this->whenPivotLoaded(
                'workspace_members',
                fn() => $this->pivot->role,
                fn() => $this->roleFor($request->user())
            ),
            'created_at' => $this->created_at,
        ];
    }
}
