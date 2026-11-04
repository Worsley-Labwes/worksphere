<?php

namespace App\Models;

use Config\Database;

class ActivityLog
{
    public static function record(int $workspaceId, ?int $userId, string $action, string $description): void
    {
        Database::connect()->prepare(
            'INSERT INTO activity_logs (workspace_id, user_id, action, description) VALUES (?, ?, ?, ?)'
        )->execute([$workspaceId, $userId, $action, mb_substr($description, 0, 255)]);
    }

    public static function recent(int $workspaceId, int $limit = 15): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT a.action, a.description, a.created_at, u.name AS user_name
             FROM activity_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.workspace_id = ?
             ORDER BY a.id DESC
             LIMIT ' . (int) $limit
        );
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll();
    }
}
