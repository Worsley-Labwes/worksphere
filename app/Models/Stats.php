<?php

namespace App\Models;

use Config\Database;
use PDO;

class Stats
{
    public static function forWorkspace(int $workspaceId, int $userId): array
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare('SELECT status, COUNT(*) FROM projects WHERE workspace_id = ? GROUP BY status');
        $stmt->execute([$workspaceId]);
        $projects = array_merge(
            ['planning' => 0, 'active' => 0, 'completed' => 0, 'archived' => 0],
            array_map('intval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR))
        );

        $stmt = $pdo->prepare('SELECT status, COUNT(*) FROM tasks WHERE workspace_id = ? GROUP BY status');
        $stmt->execute([$workspaceId]);
        $tasks = array_merge(
            ['todo' => 0, 'in_progress' => 0, 'done' => 0],
            array_map('intval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR))
        );

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM tasks
             WHERE workspace_id = ? AND status <> 'done' AND due_date IS NOT NULL AND due_date < CURDATE()"
        );
        $stmt->execute([$workspaceId]);
        $overdue = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE workspace_id = ? AND assigned_to = ? AND status <> 'done'");
        $stmt->execute([$workspaceId, $userId]);
        $mine = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT u.name, COUNT(t.id) AS assigned, SUM(t.status = 'done') AS done
             FROM tasks t
             JOIN users u ON u.id = t.assigned_to
             WHERE t.workspace_id = ?
             GROUP BY u.id, u.name
             ORDER BY done DESC, assigned DESC, u.name"
        );
        $stmt->execute([$workspaceId]);
        $team = array_map(fn($r) => [
            'name'     => $r['name'],
            'assigned' => (int) $r['assigned'],
            'done'     => (int) $r['done'],
        ], $stmt->fetchAll());

        $totalTasks = array_sum($tasks);

        return [
            'projects'       => $projects,
            'total_projects' => array_sum($projects),
            'tasks'          => $tasks,
            'total_tasks'    => $totalTasks,
            'overdue'        => $overdue,
            'mine'           => $mine,
            'completion'     => $totalTasks ? (int) round($tasks['done'] / $totalTasks * 100) : 0,
            'team'           => $team,
        ];
    }
}
