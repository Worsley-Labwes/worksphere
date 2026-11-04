<?php

namespace Tests;

use App\Models\{Invitation, Workspace};

class InvitationTest extends DatabaseTestCase
{
    private int $alice;
    private int $bob;
    private int $wsA;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = $this->makeUser('Alice');
        $this->bob   = $this->makeUser('Bob');
        $this->wsA   = Workspace::create('Company A', $this->alice);
        $this->token = Invitation::create($this->wsA, 'bob@example.com', 'member', $this->alice);
    }

    public function test_accepting_adds_the_member_with_the_invited_role(): void
    {
        Invitation::accept(Invitation::findPending($this->token), $this->bob);

        $workspace = Workspace::findForUser($this->wsA, $this->bob);
        $this->assertNotNull($workspace);
        $this->assertSame('member', $workspace['role']);
    }

    public function test_an_accepted_invitation_cannot_be_reused(): void
    {
        Invitation::accept(Invitation::findPending($this->token), $this->bob);

        $this->assertNull(Invitation::findPending($this->token));
    }

    public function test_an_expired_invitation_is_not_found(): void
    {
        $this->pdo->exec('UPDATE invitations SET expires_at = DATE_SUB(NOW(), INTERVAL 1 DAY)');

        $this->assertNull(Invitation::findPending($this->token));
    }

    public function test_a_declined_invitation_is_not_found(): void
    {
        Invitation::decline((int) Invitation::findPending($this->token)['id']);

        $this->assertNull(Invitation::findPending($this->token));
    }

    public function test_accepting_twice_does_not_duplicate_the_membership(): void
    {
        $invitation = Invitation::findPending($this->token);
        Invitation::accept($invitation, $this->bob);
        Invitation::accept($invitation, $this->bob);

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM workspace_members WHERE workspace_id = ? AND user_id = ?');
        $stmt->execute([$this->wsA, $this->bob]);
        $this->assertSame(1, (int) $stmt->fetchColumn());
    }

    public function test_a_pending_invitation_is_detected(): void
    {
        $this->assertTrue(Invitation::existsPending($this->wsA, 'bob@example.com'));
        $this->assertFalse(Invitation::existsPending($this->wsA, 'nobody@example.com'));
    }
}
