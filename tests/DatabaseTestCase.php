<?php

namespace Tests;

use App\Models\{Project, Task, User};
use Config\Database;
use PDO;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        if (!str_ends_with($_ENV['DB_NAME'] ?? '', '_test')) {
            $this->fail('Refusing to run: DB_NAME must end with _test.');
        }

        $this->pdo = Database::connect();
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['activity_logs', 'tasks', 'projects', 'invitations', 'workspace_members', 'workspaces', 'users'] as $table) {
            $this->pdo->exec("TRUNCATE TABLE $table");
        }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    protected function makeUser(string $name): int
    {
        return User::create($name, strtolower($name) . '@example.com', 'password123');
    }

    protected function makeProject(int $workspaceId, int $userId, string $name = 'Project'): int
    {
        return Project::create(
            $workspaceId,
            ['name' => $name, 'description' => '', 'status' => 'active', 'deadline' => ''],
            $userId
        );
    }

    protected function makeTask(
        int $workspaceId,
        int $projectId,
        int $userId,
        string $title = 'Task',
        ?int $assignee = null,
        string $due = ''
    ): int {
        Task::create(
            $workspaceId,
            $projectId,
            ['title' => $title, 'description' => '', 'priority' => 'medium', 'assigned_to' => $assignee, 'due_date' => $due],
            $userId
        );
        return (int) $this->pdo->lastInsertId();
    }
}
