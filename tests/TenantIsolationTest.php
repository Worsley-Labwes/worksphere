<?php

namespace Tests;

use App\Models\{Project, Task, Workspace};

class TenantIsolationTest extends DatabaseTestCase
{
    private int $alice;
    private int $bob;
    private int $wsA;
    private int $wsB;
    private int $projectA;
    private int $taskA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = $this->makeUser('Alice');
        $this->bob   = $this->makeUser('Bob');
        $this->wsA   = Workspace::create('Company A', $this->alice);
        $this->wsB   = Workspace::create('Company B', $this->bob);

        $this->projectA = $this->makeProject($this->wsA, $this->alice, 'Secret A project');
        $this->taskA    = $this->makeTask($this->wsA, $this->projectA, $this->alice, 'Secret A task');
    }

    public function test_member_can_open_their_own_workspace(): void
    {
        $workspace = Workspace::findForUser($this->wsA, $this->alice);

        $this->assertNotNull($workspace);
        $this->assertSame('owner', $workspace['role']);
    }

    public function test_outsider_cannot_open_another_workspace(): void
    {
        $this->assertNull(Workspace::findForUser($this->wsA, $this->bob));
    }

    public function test_workspace_list_only_contains_own_workspaces(): void
    {
        $names = array_column(Workspace::forUser($this->bob), 'name');

        $this->assertSame(['Company B'], $names);
    }

    public function test_project_id_from_another_tenant_is_not_found(): void
    {
        // Bob's workspace ID combined with Alice's project ID, as in a tampered URL.
        $this->assertNull(Project::find($this->projectA, $this->wsB));
        $this->assertNotNull(Project::find($this->projectA, $this->wsA));
    }

    public function test_project_listing_is_scoped_to_the_workspace(): void
    {
        $this->assertSame([], Project::forWorkspace($this->wsB));
    }

    public function test_updating_another_tenants_project_changes_nothing(): void
    {
        Project::update($this->projectA, $this->wsB, [
            'name' => 'Hacked',
            'description' => '',
            'status' => 'archived',
            'deadline' => '',
        ]);

        $project = Project::find($this->projectA, $this->wsA);
        $this->assertSame('Secret A project', $project['name']);
        $this->assertSame('active', $project['status']);
    }

    public function test_task_from_another_tenant_is_not_found(): void
    {
        $this->assertNull(Task::find($this->taskA, $this->wsB));
        $this->assertNotNull(Task::find($this->taskA, $this->wsA));
    }

    public function test_task_listing_is_scoped_to_the_workspace(): void
    {
        $this->assertSame([], Task::forProject($this->projectA, $this->wsB));
    }

    public function test_changing_another_tenants_task_status_changes_nothing(): void
    {
        Task::updateStatus($this->taskA, $this->wsB, 'done');

        $this->assertSame('todo', Task::find($this->taskA, $this->wsA)['status']);
    }

    public function test_deleting_another_tenants_task_changes_nothing(): void
    {
        Task::delete($this->taskA, $this->wsB);

        $this->assertNotNull(Task::find($this->taskA, $this->wsA));
    }

    public function test_outsider_is_not_a_valid_assignee(): void
    {
        // TaskController relies on this check to refuse cross-tenant assignees.
        $this->assertTrue(Workspace::isMember($this->wsA, $this->alice));
        $this->assertFalse(Workspace::isMember($this->wsA, $this->bob));
    }
}
