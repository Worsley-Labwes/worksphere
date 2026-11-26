<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StatsController extends Controller
{
    /**
     * Dashboard numbers for one workspace: projects, tasks, overdue, my open tasks and team productivity.
     */
    public function __invoke(Request $request, Workspace $workspace): JsonResponse
    {
        Gate::authorize('view', $workspace);

        $projects = $this->counts(
            $workspace->projects()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            ['planning', 'active', 'completed', 'archived']
        );

        $tasks = $this->counts(
            $workspace->tasks()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            ['todo', 'in_progress', 'done']
        );
        $totalTasks = array_sum($tasks);

        $overdue = $workspace->tasks()
            ->where('status', '!=', 'done')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->count();

        $mine = $workspace->tasks()
            ->where('assigned_to', $request->user()->id)
            ->where('status', '!=', 'done')
            ->count();

        $team = $workspace->tasks()
            ->join('users', 'users.id', '=', 'tasks.assigned_to')
            ->selectRaw("users.id, users.name, count(*) as assigned, sum(tasks.status = 'done') as done")
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('done')
            ->orderByDesc('assigned')
            ->orderBy('users.name')
            ->get()
            ->map(fn($row) => [
                'id'       => (int) $row->id,
                'name'     => $row->name,
                'assigned' => (int) $row->assigned,
                'done'     => (int) $row->done,
            ])
            ->values();

        return response()->json(['data' => [
            'projects' => [
                'total'     => array_sum($projects),
                'by_status' => $projects,
            ],
            'tasks' => [
                'total'      => $totalTasks,
                'by_status'  => $tasks,
                'completion' => $totalTasks ? (int) round($tasks['done'] / $totalTasks * 100) : 0,
                'overdue'    => $overdue,
                'mine'       => $mine,
            ],
            'team' => $team,
        ]]);
    }

    private function counts($rows, array $keys): array
    {
        $result = array_fill_keys($keys, 0);

        foreach ($rows as $status => $total) {
            $result[$status] = (int) $total;
        }

        return $result;
    }
}
