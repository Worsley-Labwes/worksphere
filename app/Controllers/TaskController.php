<?php

namespace App\Controllers;

use App\Models\{ActivityLog, Project, Task, Workspace};

class TaskController extends BaseController
{
    private const STATUS_LABELS = ['todo' => 'To do', 'in_progress' => 'In progress', 'done' => 'Done'];

    public function store(string $id, string $projectId): void
    {
        $workspace = $this->workspace($id);
        $wid = (int) $workspace['id'];
        $project = Project::find((int) $projectId, $wid) ?? abort(404);
        $pid = (int) $project['id'];

        $data = [
            'title'       => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'priority'    => $_POST['priority'] ?? 'medium',
            'due_date'    => trim($_POST['due_date'] ?? ''),
            'assigned_to' => null,
        ];
        $assignee = trim($_POST['assigned_to'] ?? '');

        $errors = [];
        if (in_array($project['status'], ['completed', 'archived'], true)) {
            $errors['title'] = 'Tasks cannot be added to a completed or archived project.';
        } elseif ($data['title'] === '' || mb_strlen($data['title']) > 200) {
            $errors['title'] = 'Task title is required (max 200 characters).';
        }
        if (!in_array($data['priority'], ['low', 'medium', 'high'], true)) {
            $errors['priority'] = 'Invalid priority.';
        }
        if ($data['due_date'] !== '' && !valid_date($data['due_date'])) {
            $errors['due_date'] = 'Enter a valid date.';
        }
        if ($assignee !== '') {
            // Never trust the submitted user ID: it must belong to THIS workspace.
            if (!Workspace::isMember($wid, (int) $assignee)) {
                $errors['assigned_to'] = 'Pick a member of this workspace.';
            } else {
                $data['assigned_to'] = (int) $assignee;
            }
        }

        if ($errors) {
            $_SESSION['task_errors'] = $errors;
            $_SESSION['task_old'] = $data;
            redirect("/workspaces/$wid/projects/$pid");
        }

        Task::create($wid, $pid, $data, auth_user()['id']);
        ActivityLog::record($wid, auth_user()['id'], 'task.created', "added task “{$data['title']}” to “{$project['name']}”");
        redirect("/workspaces/$wid/projects/$pid");
    }

    public function updateStatus(string $id, string $taskId): void
    {
        $workspace = $this->workspace($id);
        $wid = (int) $workspace['id'];
        $task = Task::find((int) $taskId, $wid) ?? abort(404);
        $me = auth_user()['id'];

        $allowed = $this->isManager($workspace)
            || (int) $task['assigned_to'] === $me
            || (int) $task['created_by'] === $me;
        if (!$allowed) {
            abort(403, 'Only the assignee, the creator, or an admin can move this task.');
        }

        $status = $_POST['status'] ?? '';
        if (!isset(self::STATUS_LABELS[$status])) {
            abort(422, 'Invalid status.');
        }

        Task::updateStatus((int) $task['id'], $wid, $status);
        ActivityLog::record($wid, $me, 'task.moved', "moved “{$task['title']}” to " . self::STATUS_LABELS[$status]);
        redirect("/workspaces/$wid/projects/" . (int) $task['project_id']);
    }

    public function delete(string $id, string $taskId): void
    {
        $workspace = $this->workspace($id);
        $wid = (int) $workspace['id'];
        $task = Task::find((int) $taskId, $wid) ?? abort(404);

        $allowed = $this->isManager($workspace) || (int) $task['created_by'] === auth_user()['id'];
        if (!$allowed) {
            abort(403, 'Only the creator or an admin can delete this task.');
        }

        Task::delete((int) $task['id'], $wid);
        ActivityLog::record($wid, auth_user()['id'], 'task.deleted', "deleted task “{$task['title']}”");
        redirect("/workspaces/$wid/projects/" . (int) $task['project_id']);
    }
}
