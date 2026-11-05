<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'project_id'  => $this->project_id,
            'title'       => $this->title,
            'description' => $this->description,
            'priority'    => $this->priority,
            'status'      => $this->status,
            'due_date'    => $this->due_date?->format('Y-m-d'),
            'overdue'     => $this->due_date !== null
                && $this->status !== 'done'
                && $this->due_date->lt(today()),
            'assigned_to' => $this->assigned_to,
            'assignee'    => $this->whenLoaded(
                'assignee',
                fn() => $this->assignee ? new UserResource($this->assignee) : null
            ),
            'created_by'  => $this->created_by,
            'created_at'  => $this->created_at,
        ];
    }
}
