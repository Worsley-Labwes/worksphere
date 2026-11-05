<?php

namespace Tests;

use App\Models\{ActivityLog, Stats, Task, Workspace};

class StatsAndActivityTest extends DatabaseTestCase
{
    private int $alice;
    private int $bob;
    private int $wsA;
    private int $wsB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = $this->makeUser('Alice');
        $this->bob   = $this->makeUser('Bob');
        $this->wsA   = Workspace::create('Company A', $this->alice);
        $this->wsB   = Workspace::create('Company B', $this->bob);

        $projectA = $this->makeProject($this->wsA, $this->alice);
        $projectB = $this->makeProject($this->wsB, $this->bob);

        // Company A: one overdue (todo), one done (past due date), one in progress.
        $this->makeTask($this->wsA, $projectA, $this->alice, 'Overdue', $this->alice, date('Y-m-d', strtotime('-2 days')));
        $done     = $this->makeTask($this->wsA, $projectA, $this->alice, 'Finished', $this->alice, date('Y-m-d', strtotime('-3 days')));
        $progress = $this->makeTask($this->wsA, $projectA, $this->alice, 'Upcoming', null, date('Y-m-d', strtotime('+2 days')));
        Task::updateStatus($done, $this->wsA, 'done');
        Task::updateStatus($progress, $this->wsA, 'in_progress');

        // Company B: must never leak into Company A's numbers.
        $this->makeTask($this->wsB, $projectB, $this->bob, 'Company B task', $this->bob);
    }

    public function test_counts_only_cover_the_requested_workspace(): void
    {
        $stats = Stats::forWorkspace($this->wsA, $this->alice);

        $this->assertSame(1, $stats['total_projects']);
        $this->assertSame(3, $stats['total_tasks']);
        $this->assertSame(['todo' => 1, 'in_progress' => 1, 'done' => 1], $stats['tasks']);
        $this->assertSame(33, $stats['completion']);
    }

    public function test_overdue_ignores_finished_tasks(): void
    {
        $this->assertSame(1, Stats::forWorkspace($this->wsA, $this->alice)['overdue']);
    }

    public function test_my_open_tasks_counts_only_my_unfinished_tasks(): void
    {
        $this->assertSame(1, Stats::forWorkspace($this->wsA, $this->alice)['mine']);
        $this->assertSame(0, Stats::forWorkspace($this->wsA, $this->bob)['mine']);
    }

    public function test_team_productivity_per_member(): void
    {
        $team = Stats::forWorkspace($this->wsA, $this->alice)['team'];

        $this->assertCount(1, $team);
        $this->assertSame('Alice', $team[0]['name']);
        $this->assertSame(2, $team[0]['assigned']);
        $this->assertSame(1, $team[0]['done']);
    }

    public function test_activity_feed_is_scoped_and_newest_first(): void
    {
        ActivityLog::record($this->wsA, $this->alice, 'a', 'first');
        ActivityLog::record($this->wsA, $this->alice, 'b', 'second');
        ActivityLog::record($this->wsB, $this->bob, 'c', 'company b secret');

        $feed = ActivityLog::recent($this->wsA);

        $this->assertCount(2, $feed);
        $this->assertSame('second', $feed[0]['description']);
        $this->assertNotContains('company b secret', array_column($feed, 'description'));
    }
}
