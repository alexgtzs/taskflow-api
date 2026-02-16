<?php

namespace Tests\Feature\Api\V1\Project;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('manager');
    }

    // ── Index ────────────────────────────────────────────

    public function test_user_can_list_own_projects(): void
    {
        Project::factory()->count(3)->create(['user_id' => $this->user->id]);

        // Another user's project — should NOT appear
        Project::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/projects');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_projects_are_paginated(): void
    {
        Project::factory()->count(20)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/projects');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);

        $this->assertLessThanOrEqual(15, count($response->json('data')));
    }

    public function test_projects_can_be_filtered_by_status(): void
    {
        Project::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);
        Project::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/projects?status=active');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_unauthenticated_user_cannot_list_projects(): void
    {
        $response = $this->getJson('/api/v1/projects');

        $response->assertStatus(401);
    }

    // ── Store ────────────────────────────────────────────

    public function test_user_can_create_project(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/projects', [
                'name' => 'My New Project',
                'description' => 'A great project.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'My New Project')
            ->assertJsonPath('message', 'Project created successfully.');

        $this->assertDatabaseHas('projects', [
            'name' => 'My New Project',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_project_defaults_to_active_status(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/projects', [
                'name' => 'Default Status Project',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_store_fails_without_name(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/projects', [
                'description' => 'No name provided.',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_fails_with_invalid_status(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/projects', [
                'name' => 'Test Project',
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // ── Show ─────────────────────────────────────────────

    public function test_user_can_view_own_project(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/projects/{$project->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $project->id)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'description', 'status', 'owner', 'tasks_count', 'created_at', 'updated_at'],
            ]);
    }

    public function test_user_cannot_view_others_project(): void
    {
        $otherProject = Project::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/projects/{$otherProject->id}");

        $response->assertStatus(403);
    }

    public function test_show_returns_404_for_nonexistent_project(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/projects/99999');

        $response->assertStatus(404);
    }

    // ── Update ───────────────────────────────────────────

    public function test_user_can_update_own_project(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/projects/{$project->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('message', 'Project updated successfully.');
    }

    public function test_user_cannot_update_others_project(): void
    {
        $otherProject = Project::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/projects/{$otherProject->id}", [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_update_project_status(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/projects/{$project->id}", [
                'status' => 'completed',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed');
    }

    // ── Delete ───────────────────────────────────────────

    public function test_user_can_delete_own_project(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Project deleted successfully.');

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_user_cannot_delete_others_project(): void
    {
        $otherProject = Project::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/projects/{$otherProject->id}");

        $response->assertStatus(403);
    }

    // ── Permission ───────────────────────────────────────

    public function test_member_cannot_create_project(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $response = $this->actingAs($member, 'sanctum')
            ->postJson('/api/v1/projects', [
                'name' => 'Should Fail',
            ]);

        $response->assertStatus(403);
    }

    public function test_member_cannot_delete_project(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $project = Project::factory()->create(['user_id' => $member->id]);

        $response = $this->actingAs($member, 'sanctum')
            ->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(403);
    }
}