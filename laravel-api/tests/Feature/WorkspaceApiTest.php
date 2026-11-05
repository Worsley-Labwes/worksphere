<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkspaceApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkspace(User $owner, string $name = 'Company A'): Workspace
    {
        $workspace = Workspace::create(['name' => $name, 'owner_id' => $owner->id]);
        $workspace->members()->attach($owner->id, ['role' => 'owner']);

        return $workspace;
    }

    private function registrationPayload(string $email = 'Alice@Example.com'): array
    {
        return [
            'name' => 'Alice',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    }

    public function test_user_can_register_and_receives_a_token(): void
    {
        $this->postJson('/api/register', $this->registrationPayload())
            ->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token'])
            ->assertJsonPath('user.email', 'alice@example.com');
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);

        $this->postJson('/api/register', $this->registrationPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_login_returns_a_token(): void
    {
        User::factory()->create(['email' => 'alice@example.com']); // factory password: "password"

        $this->postJson('/api/login', ['email' => 'alice@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_login_with_a_wrong_password_fails(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);

        $this->postJson('/api/login', ['email' => 'alice@example.com', 'password' => 'wrong'])
            ->assertUnprocessable();
    }

    public function test_logout_deletes_the_token(): void
    {
        $token = $this->postJson('/api/register', $this->registrationPayload())->json('token');

        $this->postJson('/api/logout', [], ['Authorization' => "Bearer $token"])->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_guests_cannot_list_workspaces(): void
    {
        $this->getJson('/api/workspaces')->assertUnauthorized();
    }

    public function test_creating_a_workspace_makes_the_creator_the_owner(): void
    {
        Sanctum::actingAs($alice = User::factory()->create());

        $this->postJson('/api/workspaces', ['name' => 'Company A'])
            ->assertCreated()
            ->assertJsonPath('data.role', 'owner');

        $this->assertDatabaseHas('workspace_members', ['user_id' => $alice->id, 'role' => 'owner']);
    }

    public function test_workspace_name_is_required(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/workspaces', ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_a_user_only_lists_their_own_workspaces(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $this->makeWorkspace($alice, 'Company A');
        $this->makeWorkspace($bob, 'Company B');

        Sanctum::actingAs($bob);

        $this->getJson('/api/workspaces')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Company B');
    }

    public function test_a_member_can_view_their_workspace(): void
    {
        $alice = User::factory()->create();
        $workspace = $this->makeWorkspace($alice);

        Sanctum::actingAs($alice);

        $this->getJson("/api/workspaces/{$workspace->id}")
            ->assertOk()
            ->assertJsonPath('data.role', 'owner');
    }

    public function test_an_outsider_gets_404_for_another_tenants_workspace(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $workspace = $this->makeWorkspace($alice);

        Sanctum::actingAs($bob);

        $this->getJson("/api/workspaces/{$workspace->id}")->assertNotFound();
    }
}
