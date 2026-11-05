<?php

namespace App\Controllers;

use App\Models\{ActivityLog, Project, Task, Workspace};

class ProjectController extends BaseController
{
    private const STATUSES = ['planning', 'active', 'completed', 'archived'];

    public function index(string $id): void
    {
        $workspace = $this->workspace($id);

        view('projects/index', [
            'workspace' => $workspace,
            'projects'  => Project::forWorkspace((int) $workspace['id']),
            'canManage' => $this->isManager($workspace),
        ]);
    }

    public function create(string $id): void
    {
        $workspace = $this->workspace($id);
        $this->requireManager($workspace);

        view('projects/form', [
            'workspace' => $workspace,
            'project'   => null,
            'errors'    => pull('errors'),
            'old'       => pull('old'),
        ]);
    }

    public function store(string $id): void
    {
        $workspace = $this->workspace($id);
        $this->requireManager($workspace);
        $wid = (int) $workspace['id'];

        [$data, $errors] = $this->validated();
        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            redirect("/workspaces/$wid/projects/create");
        }

        $projectId = Project::create($wid, $data, auth_user()['id']);
        ActivityLog::record($wid, auth_user()['id'], 'project.created', "created project “{$data['name']}”");
        redirect("/workspaces/$wid/projects/$projectId");
    }

    public function show(string $id, string $projectId): void
    {
        $workspace = $this->workspace($id);
        $wid = (int) $workspace['id'];
        $project = Project::find((int) $projectId, $wid) ?? abort(404);

        view('projects/show', [
            'workspace'  => $workspace,
            'project'    => $project,
            'tasks'      => Task::forProject((int) $project['id'], $wid),
            'members'    => Workspace::memberList($wid),
            'canManage'  => $this->isManager($workspace),
            'taskErrors' => pull('task_errors'),
            'taskOld'    => pull('task_old'),
        ]);
    }

    public function edit(string $id, string $projectId): void
    {
        $workspace = $this->workspace($id);
        $this->requireManager($workspace);
        $project = Project::find((int) $projectId, (int) $workspace['id']) ?? abort(404);

        view('projects/form', [
            'workspace' => $workspace,
            'project'   => $project,
            'errors'    => pull('errors'),
            'old'       => pull('old'),
        ]);
    }

    public function update(string $id, string $projectId): void
    {
        $workspace = $this->workspace($id);
        $this->requireManager($workspace);
        $wid = (int) $workspace['id'];
        $project = Project::find((int) $projectId, $wid) ?? abort(404);
        $pid = (int) $project['id'];

        [$data, $errors] = $this->validated();
        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            redirect("/workspaces/$wid/projects/$pid/edit");
        }

        Project::update($pid, $wid, $data);

        if ($project['status'] !== $data['status']) {
            ActivityLog::record($wid, auth_user()['id'], 'project.status', "marked project “{$data['name']}” as {$data['status']}");
        } else {
            ActivityLog::record($wid, auth_user()['id'], 'project.updated', "updated project “{$data['name']}”");
        }

        redirect("/workspaces/$wid/projects/$pid");
    }

    private function validated(): array
    {
        $data = [
            'name'        => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'deadline'    => trim($_POST['deadline'] ?? ''),
            'status'      => $_POST['status'] ?? 'planning',
        ];

        $errors = [];
        if ($data['name'] === '' || mb_strlen($data['name']) > 150) {
            $errors['name'] = 'Project name is required (max 150 characters).';
        }
        if ($data['deadline'] !== '' && !valid_date($data['deadline'])) {
            $errors['deadline'] = 'Enter a valid date.';
        }
        if (!in_array($data['status'], self::STATUSES, true)) {
            $errors['status'] = 'Invalid status.';
        }
        return [$data, $errors];
    }
}
