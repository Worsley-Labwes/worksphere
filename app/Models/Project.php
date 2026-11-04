<?php

namespace App\Models;

use Config\Database;

class Project
{
    public static function forWorkspace(int $workspaceId): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT p.*,
                (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) AS task_count,
                (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'done') AS done_count
             FROM projects p
             WHERE p.workspace_id = ?
             ORDER BY (p.status = 'archived'), p.created_at DESC"
        );
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll();
    }

    // Always looked up by workspace AND id: a project ID from another tenant returns null.
    public static function find(int $id, int $workspaceId): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM projects WHERE id = ? AND workspace_id = ? LIMIT 1');
        $stmt->execute([$id, $workspaceId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $workspaceId, array $d, int $createdBy): int
    {
        $pdo = Database::connect();
        $pdo->prepare(
            'INSERT INTO projects (workspace_id, name, description, status, deadline, created_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $workspaceId,
            $d['name'],
            $d['description'] !== '' ? $d['description'] : null,
            $d['status'],
            $d['deadline'] !== '' ? $d['deadline'] : null,
            $createdBy,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, int $workspaceId, array $d): void
    {
        Database::connect()->prepare(
            'UPDATE projects SET name = ?, description = ?, status = ?, deadline = ?
             WHERE id = ? AND workspace_id = ?'
        )->execute([
            $d['name'],
            $d['description'] !== '' ? $d['description'] : null,
            $d['status'],
            $d['deadline'] !== '' ? $d['deadline'] : null,
            $id,
            $workspaceId,
        ]);
    }
}
