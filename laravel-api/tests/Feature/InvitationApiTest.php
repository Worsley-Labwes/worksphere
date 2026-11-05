<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class InvitationApiTest extends TestCase
{
    use BuildsTenants, RefreshDatabase;

    public function test_an_owner_can_invite_and_receives_the_token(): void
    {
        $alice = User::factory()->create();
        $workspace = $this->createWorkspace($alice);
        Sanctum::actingAs($alice);

        $this->postJson("/api/workspaces/{$workspace->id}/invitations", ['email' => 'Bob@Example.com'])
            ->assertCreated()
            ->assertJsonPath('data.email', 'bob@example.com')
            ->assertJsonPath('data.role', 'member')
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_an_admin_can_invite_members_but_not_admins(): void
    {
        $alice = User::factory()->create();
        $carol = User::factory()->create();
        $workspace = $this->createWorkspace($alice);
        $this->addMember($workspace, $carol, 'admin');
        Sanctum::actingAs($carol);

        $url = "/api/workspaces/{$workspace->id}/invitations";

        $this->postJson($url, ['email' => 'a@example.com', 'role' => 'admin'])->assertForbidden();
        $this->postJson($url, ['email' => 'b@example.com'])->assertCreated();
    }

    public function test_a_plain_member_cannot_invite(): void
    {
        $alice = User::factory()->create();
        $carol = User::factory()->create();
        $workspace = $this->createWorkspace($alice);
        $this->addMember($workspace, $carol);
        Sanctum::actingAs($carol);

        $this->postJson("/api/workspaces/{$workspace->id}/invitations", ['email' => 'x@example.com'])
            ->assertForbidden();
    }

    public function test_an_outsider_gets_404_inviting_into_another_tenant(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $workspace = $this->createWorkspace($alice);
        Sanctum::actingAs($bob);

        $this->postJson("/api/workspaces/{$workspace->id}/invitations", ['email' => 'x@example.com'])
            ->assertNotFound();
    }

    public function test_duplicates_and_existing_members_are_rejected(): void
    {
        $alice = User::factory()->create();
        $carol = User::factory()->create(['email' => 'carol@example.com']);
        $workspace = $this->createWorkspace($alice);
        $this->addMember($workspace, $carol);
        Sanctum::actingAs($alice);

        $url = "/api/workspaces/{$workspace->id}/invitations";

        $this->postJson($url, ['email' => 'dave@example.com'])->assertCreated();
        $this->postJson($url, ['email' => 'dave@example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson($url, ['email' => 'carol@example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_an_invitee_can_see_and_accept_an_invitation(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create(['email' => 'bob@example.com']);
        $workspace = $this->createWorkspace($alice);

        Sanctum::actingAs($alice);
        $token = $this->postJson("/api/workspaces/{$workspace->id}/invitations", ['email' => 'bob@example.com'])
            ->json('data.token');

        Sanctum::actingAs($bob);

        $this->getJson('/api/invitations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.token', $token);

        $this->postJson("/api/invitations/{$token}/accept")
            ->assertOk()
            ->assertJsonPath('data.role', 'member');

        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $bob->id,
            'role' => 'member',
        ]);
    }

    public function test_the_wrong_user_cannot_accept_an_invitation(): void
    {
        $alice = User::factory()->create();
        $carol = User::factory()->create();
        $workspace = $this->createWorkspace($alice);

        Sanctum::actingAs($alice);
        $token = $this->postJson("/api/workspaces/{$workspace->id}/invitations", ['email' => 'bob@example.com'])
            ->json('data.token');

        Sanctum::actingAs($carol);

        $this->postJson("/api/invitations/{$token}/accept")->assertNotFound();
        $this->assertDatabaseMissing('workspace_members', ['user_id' => $carol->id]);
    }

    public function test_an_invitation_cannot_be_used_twice(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create(['email' => 'bob@example.com']);
        $workspace = $this->createWorkspace($alice);

        Sanctum::actingAs($alice);
        $token = $this->postJson("/api/workspaces/{$workspace->id}/invitations", ['email' => 'bob@example.com'])
            ->json('data.token');

        Sanctum::actingAs($bob);
        $this->postJson("/api/invitations/{$token}/accept")->assertOk();
        $this->postJson("/api/invitations/{$token}/accept")->assertNotFound();
    }

    public function test_an_expired_invitation_cannot_be_accepted(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create(['email' => 'bob@example.com']);
        $workspace = $this->createWorkspace($alice);

        Sanctum::actingAs($alice);
        $token = $this->postJson("/api/workspaces/{$workspace->id}/invitations", ['email' => 'bob@example.com'])
            ->json('data.token');

        Invitation::query()->update(['expires_at' => now()->subDay()]);

        Sanctum::actingAs($bob);
        $this->postJson("/api/invitations/{$token}/accept")->assertNotFound();
    }

    public function test_declining_marks_the_invitation_and_grants_no_access(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create(['email' => 'bob@example.com']);
        $workspace = $this->createWorkspace($alice);

        Sanctum::actingAs($alice);
        $token = $this->postJson("/api/workspaces/{$workspace->id}/invitations", ['email' => 'bob@example.com'])
            ->json('data.token');

        Sanctum::actingAs($bob);
        $this->postJson("/api/invitations/{$token}/decline")->assertNoContent();

        $this->assertDatabaseHas('invitations', ['token' => $token, 'status' => 'declined']);
        $this->getJson("/api/workspaces/{$workspace->id}")->assertNotFound();
    }
}
