<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'workspace_id' => $this->workspace_id,
            'name'         => $this->name,
            'description'  => $this->description,
            'status'       => $this->status,
            'deadline'     => $this->deadline?->format('Y-m-d'),
            'tasks_count'      => $this->whenCounted('tasks'),
            'done_tasks_count' => $this->when(isset($this->done_tasks_count), fn() => (int) $this->done_tasks_count),
            'progress'         => $this->when(
                isset($this->tasks_count),
                fn() => $this->tasks_count ? (int) round($this->done_tasks_count / $this->tasks_count * 100) : 0
            ),
            'tasks'      => TaskResource::collection($this->whenLoaded('tasks')),
            'created_at' => $this->created_at,
        ];
    }
}
