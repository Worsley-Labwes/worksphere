<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class ActivityStatsApiTest extends TestCase
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

    // ---------- Activity feed ----------

    public function test_actions_are_recorded_and_listed_newest_first(): void
    {
        Sanctum::actingAs($this->alice);
        $base = "/api/workspaces/{$this->wsA->id}";

        $projectId = $this->postJson("$base/projects", ['name' => 'Launch'])->json('data.id');
        $taskId = $this->postJson("$base/projects/$projectId/tasks", ['title' => 'Write copy'])->json('data.id');
        $this->patchJson("$base/tasks/$taskId/status", ['status' => 'done'])->assertOk();

        Sanctum::actingAs($this->carol); // any member can read the feed

        $this->getJson("$base/activity")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.action', 'task.moved')
            ->assertJsonPath('data.0.user.name', $this->alice->name)
            ->assertJsonPath('data.2.action', 'project.created');
    }

    public function test_creating_a_workspace_and_joining_are_recorded(): void
    {
        Sanctum::actingAs($this->alice);
        $workspaceId = $this->postJson('/api/workspaces', ['name' => 'Fresh'])->json('data.id');

        $this->assertDatabaseHas('activity_logs', [
            'workspace_id' => $workspaceId,
            'user_id' => $this->alice->id,
            'action' => 'workspace.created',
        ]);

        $this->postJson("/api/workspaces/$workspaceId/invitations", ['email' => $this->bob->email])->assertCreated();
        $this->assertDatabaseHas('activity_logs', ['workspace_id' => $workspaceId, 'action' => 'invitation.sent']);
    }

    public function test_an_update_that_changes_nothing_is_not_logged(): void
    {
        $project = $this->createProject($this->wsA, $this->alice);
        $task = $this->createTask($project, $this->alice); // already "todo"
        Sanctum::actingAs($this->alice);

        $this->patchJson("/api/workspaces/{$this->wsA->id}/tasks/{$task->id}/status", ['status' => 'todo'])
            ->assertOk();

        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_the_feed_only_contains_the_workspaces_own_activity(): void
    {
        Sanctum::actingAs($this->bob);
        $this->postJson("/api/workspaces/{$this->wsB->id}/projects", ['name' => 'B secret'])->assertCreated();

        Sanctum::actingAs($this->alice);
        $this->getJson("/api/workspaces/{$this->wsA->id}/activity")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_an_outsider_gets_404_for_another_tenants_feed(): void
    {
        Sanctum::actingAs($this->bob);

        $this->getJson("/api/workspaces/{$this->wsA->id}/activity")->assertNotFound();
    }

    // ---------- Stats ----------

    private function seedStatsData(): void
    {
        $project = $this->createProject($this->wsA, $this->alice);
        $this->createTask($project, $this->alice, [
            'title' => 'Finished',
            'status' => 'done',
            'assigned_to' => $this->alice->id,
        ]);
        $this->createTask($project, $this->alice, [
            'title' => 'Late',
            'assigned_to' => $this->alice->id,
            'due_date' => now()->subDays(2)->toDateString(),
        ]);
        $this->createTask($project, $this->alice, [
            'title' => 'Upcoming',
            'status' => 'in_progress',
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        // Company B data must never leak into Company A's numbers.
        $projectB = $this->createProject($this->wsB, $this->bob);
        $this->createTask($projectB, $this->bob, [
            'title' => 'B task',
            'assigned_to' => $this->bob->id,
            'due_date' => now()->subDays(5)->toDateString(),
        ]);
    }

    public function test_stats_count_only_the_requested_workspace(): void
    {
        $this->seedStatsData();
        Sanctum::actingAs($this->alice);

        $this->getJson("/api/workspaces/{$this->wsA->id}/stats")
            ->assertOk()
            ->assertJsonPath('data.projects.total', 1)
            ->assertJsonPath('data.projects.by_status.active', 1)
            ->assertJsonPath('data.tasks.total', 3)
            ->assertJsonPath('data.tasks.by_status.todo', 1)
            ->assertJsonPath('data.tasks.by_status.in_progress', 1)
            ->assertJsonPath('data.tasks.by_status.done', 1)
            ->assertJsonPath('data.tasks.completion', 33);
    }

    public function test_overdue_ignores_finished_tasks_and_other_tenants(): void
    {
        $this->seedStatsData();
        Sanctum::actingAs($this->alice);

        $this->getJson("/api/workspaces/{$this->wsA->id}/stats")
            ->assertJsonPath('data.tasks.overdue', 1);
    }

    public function test_my_open_tasks_counts_only_my_unfinished_tasks(): void
    {
        $this->seedStatsData();

        Sanctum::actingAs($this->alice);
        $this->getJson("/api/workspaces/{$this->wsA->id}/stats")->assertJsonPath('data.tasks.mine', 1);

        Sanctum::actingAs($this->carol);
        $this->getJson("/api/workspaces/{$this->wsA->id}/stats")->assertJsonPath('data.tasks.mine', 0);
    }

    public function test_team_productivity_is_listed_per_member(): void
    {
        $this->seedStatsData();
        Sanctum::actingAs($this->carol);

        $this->getJson("/api/workspaces/{$this->wsA->id}/stats")
            ->assertJsonCount(1, 'data.team')
            ->assertJsonPath('data.team.0.name', $this->alice->name)
            ->assertJsonPath('data.team.0.assigned', 2)
            ->assertJsonPath('data.team.0.done', 1);
    }

    public function test_an_outsider_gets_404_for_another_tenants_stats(): void
    {
        $this->seedStatsData();
        Sanctum::actingAs($this->bob);

        $this->getJson("/api/workspaces/{$this->wsA->id}/stats")->assertNotFound();
    }
}
