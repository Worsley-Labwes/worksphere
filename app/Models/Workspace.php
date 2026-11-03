<?php

namespace App\Models;

use Config\Database;

class Workspace
{
    public static function create(string $name, int $ownerId): int
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO workspaces (name, owner_id) VALUES (?, ?)')
                ->execute([$name, $ownerId]);
            $id = (int) $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO workspace_members (workspace_id, user_id, role) VALUES (?, ?, 'owner')")
                ->execute([$id, $ownerId]);

            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function forUser(int $userId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT w.*, m.role FROM workspaces w
             JOIN workspace_members m ON m.workspace_id = w.id
             WHERE m.user_id = ?
             ORDER BY w.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // The tenant-isolation gate: returns the workspace ONLY if this user is a member.
    public static function findForUser(int $workspaceId, int $userId): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT w.*, m.role FROM workspaces w
             JOIN workspace_members m ON m.workspace_id = w.id
             WHERE w.id = ? AND m.user_id = ?
             LIMIT 1'
        );
        $stmt->execute([$workspaceId, $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function members(int $workspaceId): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT u.name, u.email, m.role FROM workspace_members m
             JOIN users u ON u.id = m.user_id
             WHERE m.workspace_id = ?
             ORDER BY FIELD(m.role, 'owner', 'admin', 'member'), u.name"
        );
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll();
    }

    public static function hasMemberWithEmail(int $workspaceId, string $email): bool
    {
        $stmt = Database::connect()->prepare(
            'SELECT 1 FROM workspace_members m
             JOIN users u ON u.id = m.user_id
             WHERE m.workspace_id = ? AND u.email = ?
             LIMIT 1'
        );
        $stmt->execute([$workspaceId, $email]);
        return (bool) $stmt->fetchColumn();
    }
}
