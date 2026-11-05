<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class ProjectTaskApiTest extends TestCase
{
    use BuildsTenants, RefreshDatabase;

    private User $alice; // owner of Company A
    private User $bob;   // owner of Company B
    private User $carol; // plain member of Company A
    private Workspace $wsA;
    private Workspace $wsB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = User::factory()->create();
        $this->bob = User::factory()->create();
        $this->carol = User::factory()->create();

        $this->wsA = $this->createWorkspace($this->alice, 'Company A');
        $this->wsB = $this->createWorkspace($this->bob, 'Company B');
        $this->addMember($this->wsA, $this->carol);
    }

    // ---------- Projects ----------

    public function test_an_owner_can_create_a_project(): void
    {
        Sanctum::actingAs($this->alice);

        $this->postJson("/api/workspaces/{$this->wsA->id}/projects", [
            'name' => 'Website redesign',
            'deadline' => '2026-12-31',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Website redesign')
            ->assertJsonPath('data.status', 'planning')
            ->assertJsonPath('data.deadline', '2026-12-31')
            ->assertJsonPath('data.progress', 0);

        $this->assertDatabaseHas('projects', [
            'workspace_id' => $this->wsA->id,
            'created_by' => $this->alice->id,
        ]);
    }

    public function test_a_plain_member_cannot_create_a_project(): void
    {
        Sanctum::actingAs($this->carol);

        $this->postJson("/api/workspaces/{$this->wsA->id}/projects", ['name' => 'Nope'])->assertForbidden();
    }

    public function test_an_outsider_gets_404_creating_a_project_in_another_tenant(): void
    {
        Sanctum::actingAs($this->bob);

        $this->postJson("/api/workspaces/{$this->wsA->id}/projects", ['name' => 'Nope'])->assertNotFound();
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_project_input_is_validated(): void
    {
        Sanctum::actingAs($this->alice);
        $url = "/api/workspaces/{$this->wsA->id}/projects";

        $this->postJson($url, [])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson($url, ['name' => 'X', 'deadline' => '31/12/2026'])->assertJsonValidationErrors('deadline');
        $this->postJson($url, ['name' => 'X', 'status' => 'nope'])->assertJsonValidationErrors('status');
    }

    public function test_projects_are_listed_per_workspace(): void
    {
        $this->createProject($this->wsA, $this->alice, ['name' => 'A project']);
        $this->createProject($this->wsB, $this->bob, ['name' => 'B project']);

        Sanctum::actingAs($this->alice);

        $this->getJson("/api/workspaces/{$this->wsA->id}/projects")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'A project');
    }

    public function test_outsiders_cannot_list_or_view_another_tenants_projects(): void
    {
        $project = $this->createProject($this->wsA, $this->alice);
        Sanctum::actingAs($this->bob);

        $this->getJson("/api/workspaces/{$this->wsA->id}/projects")->assertNotFound();
        $this->getJson("/api/workspaces/{$this->wsA->id}/projects/{$project->id}")->assertNotFound();
    }

    public function test_a_project_id_from_another_tenant_is_not_found_in_my_workspace(): void
    {
        $projectA = $this->createProject($this->wsA, $this->alice);
        Sanctum::actingAs($this->bob);

        // Bob's own workspace ID combined with Alice's project ID, as in a tampered URL.
        $this->getJson("/api/workspaces/{$this->wsB->id}/projects/{$projectA->id}")->assertNotFound();
    }

    public function test_a_member_can_view_a_project_with_its_tasks(): void
    {
        $project = $this->createProject($this->wsA, $this->alice);
        $this->createTask($project, $this->alice, ['title' => 'First task']);
        Sanctum::actingAs($this->carol);

        $this->getJson("/api/workspaces/{$this->wsA->id}/projects/{$project->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.tasks')
            ->assertJsonPath('data.tasks.0.title', 'First task');
    }

    public function test_only_managers_can_update_a_project(): void
    {
        $project = $this->createProject($this->wsA, $this->alice);
        $url = "/api/workspaces/{$this->wsA->id}/projects/{$project->id}";

        Sanctum::actingAs($this->carol);
        $this->patchJson($url, ['status' => 'archived'])->assertForbidden();

        Sanctum::actingAs($this->alice);
        $this->patchJson($url, ['status' => 'archived'])->assertOk()->assertJsonPath('data.status', 'archived');
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'archived']);
    }

    public function test_progress_and_overdue_flags_are_calculated(): void
    {
        $project = $this->createProject($this->wsA, $this->alice);
        $this->createTask($project, $this->alice, ['title' => 'Finished', 'status' => 'done']);
        $this->createTask($project, $this->alice, [
            'title' => 'Late',
            'due_date' => now()->subDays(2)->toDateString(),
        ]);
        Sanctum::actingAs($this->alice);

        $response = $this->getJson("/api/workspaces/{$this->wsA->id}/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.tasks_count', 2)
            ->assertJsonPath('data.done_tasks_count', 1)
            ->assertJsonPath('data.progress', 50);

        $response->assertJsonFragment(['title' => 'Late', 'overdue' => true]);
        $response->assertJsonFragment(['title' => 'Finished', 'overdue' => false]);
    }

    // ---------- Tasks ----------

    public function test_a_member_can_add_a_task_assigned_to_a_member(): void
    {
        $project = $this->createProject($this->wsA, $this->alice);
        Sanctum::actingAs($this->carol);

        $this->postJson("/api/workspaces/{$this->wsA->id}/projects/{$project->id}/tasks", [
            'title' => 'Write copy',
            'priority' => 'high',
            'assigned_to' => $this->carol->id,
            'due_date' => '2026-12-01',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'todo')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.assignee.id', $this->carol->id);
    }

    public function test_the_assignee_must_belong_to_the_workspace(): void
    {
        $project = $this->createProject($this->wsA, $this->alice);
        Sanctum::actingAs($this->alice);

        $this->postJson("/api/workspaces/{$this->wsA->id}/projects/{$project->id}/tasks", [
            'title' => 'Write copy',
            'assigned_to' => $this->bob->id, // a member of Company B only
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('assigned_to');
    }

    public function test_tasks_cannot_be_added_to_an_archived_project(): void
    {
        $project = $this->createProject($this->wsA, $this->alice, ['status' => 'archived']);
        Sanctum::actingAs($this->alice);

        $this->postJson("/api/workspaces/{$this->wsA->id}/projects/{$project->id}/tasks", ['title' => 'Late idea'])
            ->assertUnprocessable();
    }

    public function test_task_input_is_validated(): void
    {
        $project = $this->createProject($this->wsA, $this->alice);
        Sanctum::actingAs($this->alice);
        $url = "/api/workspaces/{$this->wsA->id}/projects/{$project->id}/tasks";

        $this->postJson($url, [])->assertUnprocessable()->assertJsonValidationErrors('title');
        $this->postJson($url, ['title' => 'X', 'priority' => 'urgent'])->assertJsonValidationErrors('priority');
    }

    public function test_an_outsider_cannot_add_tasks_to_another_tenants_project(): void
    {
        $project = $this->createProject($this->wsA, $this->alice);
        Sanctum::actingAs($this->bob);

        $this->postJson("/api/workspaces/{$this->wsA->id}/projects/{$project->id}/tasks", ['title' => 'Sneaky'])
            ->assertNotFound();
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_the_assignee_can_move_their_task(): void
    {
        $project = $this->createProject($this->wsA, $this->alice);
        $task = $this->createTask($project, $this->alice, ['assigned_to' => $this->carol->id]);
        Sanctum::actingAs($this->carol);

        $this->patchJson("/api/workspaces/{$this->wsA->id}/tasks/{$task->id}/status", ['status' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_an_unrelated_member_cannot_move_a_task(): void
    {
        $dave = User::factory()->create();
        $this->addMember($this->wsA, $dave);
        $project = $this->createProject($this->wsA, $this->alice);
        $task = $this->createTask($project, $this->alice, ['assigned_to' => $this->carol->id]);
        Sanctum::actingAs($dave);

        $this->patchJson("/api/workspaces/{$this->wsA->id}/tasks/{$task->id}/status", ['status' => 'done'])
            ->assertForbidden();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'todo']);
    }

    public function test_a_manager_can_move_any_task(): void
    {
        $project = $this->createProject($this->wsA, $this->alice);
        $task = $this->createTask($project, $this->carol, ['assigned_to' => $this->carol->id]);
        Sanctum::actingAs($this->alice);

        $this->patchJson("/api/workspaces/{$this->wsA->id}/tasks/{$task->id}/status", ['status' => 'done'])
            ->assertOk();
    }

    public function test_an_invalid_status_is_rejected(): void
    {
        $project = $this->createProject($this->wsA, $this->alice);
        $task = $this->createTask($project, $this->alice);
        Sanctum::actingAs($this->alice);

        $this->patchJson("/api/workspaces/{$this->wsA->id}/tasks/{$task->id}/status", ['status' => 'nope'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_a_task_id_from_another_tenant_cannot_be_changed(): void
    {
        $project = $this->createProject($this->wsA, $this->alice);
        $taskA = $this->createTask($project, $this->alice);
        Sanctum::actingAs($this->bob);

        // Through Bob's own workspace URL (scoped binding fails) ...
        $this->patchJson("/api/workspaces/{$this->wsB->id}/tasks/{$taskA->id}/status", ['status' => 'done'])
            ->assertNotFound();
        // ... and through Alice's workspace URL (the policy refuses non-members).
        $this->patchJson("/api/workspaces/{$this->wsA->id}/tasks/{$taskA->id}/status", ['status' => 'done'])
            ->assertNotFound();

        $this->assertDatabaseHas('tasks', ['id' => $taskA->id, 'status' => 'todo']);
    }

    public function test_only_the_creator_or_a_manager_can_delete_a_task(): void
    {
        $dave = User::factory()->create();
        $this->addMember($this->wsA, $dave);
        $project = $this->createProject($this->wsA, $this->alice);
        $task = $this->createTask($project, $this->carol);
        $url = "/api/workspaces/{$this->wsA->id}/tasks/{$task->id}";

        Sanctum::actingAs($dave);
        $this->deleteJson($url)->assertForbidden();

        Sanctum::actingAs($this->carol);
        $this->deleteJson($url)->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_an_outsider_cannot_delete_another_tenants_task(): void
    {
        $project = $this->createProject($this->wsA, $this->alice);
        $task = $this->createTask($project, $this->alice);
        Sanctum::actingAs($this->bob);

        $this->deleteJson("/api/workspaces/{$this->wsA->id}/tasks/{$task->id}")->assertNotFound();
        $this->deleteJson("/api/workspaces/{$this->wsB->id}/tasks/{$task->id}")->assertNotFound();

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }
}
