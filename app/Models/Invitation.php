<?php

namespace App\Models;

use Config\Database;

class Invitation
{
    public static function create(int $workspaceId, string $email, string $role, int $invitedBy): string
    {
        $token = bin2hex(random_bytes(32));
        Database::connect()->prepare(
            'INSERT INTO invitations (workspace_id, email, role, token, invited_by, expires_at)
             VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))'
        )->execute([$workspaceId, $email, $role, $token, $invitedBy]);

        return $token;
    }

    public static function findPending(string $token): ?array
    {
        $stmt = Database::connect()->prepare(
            "SELECT i.*, w.name AS workspace_name, u.name AS inviter_name
             FROM invitations i
             JOIN workspaces w ON w.id = i.workspace_id
             JOIN users u ON u.id = i.invited_by
             WHERE i.token = ? AND i.status = 'pending' AND i.expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public static function existsPending(int $workspaceId, string $email): bool
    {
        $stmt = Database::connect()->prepare(
            "SELECT 1 FROM invitations
             WHERE workspace_id = ? AND email = ? AND status = 'pending' AND expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute([$workspaceId, $email]);
        return (bool) $stmt->fetchColumn();
    }

    public static function pendingForWorkspace(int $workspaceId): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT email, role, expires_at FROM invitations
             WHERE workspace_id = ? AND status = 'pending' AND expires_at > NOW()
             ORDER BY created_at DESC"
        );
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll();
    }

    public static function pendingForEmail(string $email): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT i.token, i.role, w.name AS workspace_name
             FROM invitations i
             JOIN workspaces w ON w.id = i.workspace_id
             WHERE i.email = ? AND i.status = 'pending' AND i.expires_at > NOW()
             ORDER BY i.created_at DESC"
        );
        $stmt->execute([$email]);
        return $stmt->fetchAll();
    }

    public static function accept(array $invitation, int $userId): void
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT IGNORE INTO workspace_members (workspace_id, user_id, role) VALUES (?, ?, ?)')
                ->execute([$invitation['workspace_id'], $userId, $invitation['role']]);

            $pdo->prepare("UPDATE invitations SET status = 'accepted' WHERE id = ?")
                ->execute([$invitation['id']]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function decline(int $id): void
    {
        Database::connect()->prepare("UPDATE invitations SET status = 'declined' WHERE id = ?")->execute([$id]);
    }
}
