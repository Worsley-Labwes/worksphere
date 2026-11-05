<?php

namespace App\Models;

use Config\Database;

class Task
{
    public static function forProject(int $projectId, int $workspaceId): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT t.*, u.name AS assignee_name
             FROM tasks t
             LEFT JOIN users u ON u.id = t.assigned_to
             WHERE t.project_id = ? AND t.workspace_id = ?
             ORDER BY FIELD(t.priority, 'high', 'medium', 'low'), t.due_date IS NULL, t.due_date, t.id"
        );
        $stmt->execute([$projectId, $workspaceId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id, int $workspaceId): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM tasks WHERE id = ? AND workspace_id = ? LIMIT 1');
        $stmt->execute([$id, $workspaceId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $workspaceId, int $projectId, array $d, int $createdBy): void
    {
        Database::connect()->prepare(
            'INSERT INTO tasks (workspace_id, project_id, title, description, priority, assigned_to, due_date, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $workspaceId,
            $projectId,
            $d['title'],
            $d['description'] !== '' ? $d['description'] : null,
            $d['priority'],
            $d['assigned_to'],
            $d['due_date'] !== '' ? $d['due_date'] : null,
            $createdBy,
        ]);
    }

    public static function updateStatus(int $id, int $workspaceId, string $status): void
    {
        Database::connect()->prepare('UPDATE tasks SET status = ? WHERE id = ? AND workspace_id = ?')
            ->execute([$status, $id, $workspaceId]);
    }

    public static function delete(int $id, int $workspaceId): void
    {
        Database::connect()->prepare('DELETE FROM tasks WHERE id = ? AND workspace_id = ?')
            ->execute([$id, $workspaceId]);
    }
}
