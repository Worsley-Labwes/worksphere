<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'email'      => $this->email,
            'role'       => $this->role,
            'status'     => $this->status,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'workspace'  => $this->whenLoaded('workspace', fn() => [
                'id'   => $this->workspace->id,
                'name' => $this->workspace->name,
            ]),
            // The token is shown once, when the invitation is created, and to the invitee.
            'token' => $this->when(
                $this->wasRecentlyCreated || strtolower($request->user()->email) === strtolower($this->email),
                fn() => $this->token
            ),
        ];
    }
}
