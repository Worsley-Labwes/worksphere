<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// workspace_id and created_by are set explicitly by the controller, never mass-assigned.
#[Fillable(['name', 'description', 'status', 'deadline'])]
class Project extends Model
{
    protected function casts(): array
    {
        return ['deadline' => 'date'];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    private static function progressCounts(): array
    {
        return [
            'tasks',
            'tasks as done_tasks_count' => fn($query) => $query->where('status', 'done'),
        ];
    }

    public function scopeWithProgress(Builder $query): Builder
    {
        return $query->withCount(self::progressCounts());
    }

    public function loadProgress(): static
    {
        return $this->loadCount(self::progressCounts());
    }
}
