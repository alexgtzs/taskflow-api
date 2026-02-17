<?php

namespace Tests\Feature\Api\V1\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('manager');

        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
    }

    private function taskUrl(Task $task = null): string
    {
        $base = "/api/v1/projects/{$this->project->id}/tasks";

        return $task ? "{$base}/{$task->id}" : $base;
    }

    // ── Index ────────────────────────────────────────────

    public function test_user_can_list_project_tasks(): void
    {
        Task::factory()->count(3)->create(['project_id' => $this->project->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->taskUrl());

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_tasks_do_not_leak_from_other_projects(): void
    {
        Task::factory()->count(2)->create(['project_id' => $this->project->id]);
        Task::factory()->count(3)->create(); // other project

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->taskUrl());

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_tasks_can_be_filtered_by_status(): void
    {
        Task::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'status' => 'todo',
        ]);
        Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'done',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->taskUrl() . '?status=todo');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_tasks_can_be_filtered_by_priority(): void
    {
        Task::factory()->create([
            'project_id' => $this->project->id,
            'priority' => 'urgent',
        ]);
        Task::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'priority' => 'low',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->taskUrl() . '?priority=urgent');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_cannot_list_tasks_from_others_project(): void
    {
        $otherProject = Project::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/projects/{$otherProject->id}/tasks");

        $response->assertStatus(403);
    }

    // ── Store ────────────────────────────────────────────

    public function test_user_can_create_task(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->taskUrl(), [
                'title' => 'Implement login',
                'description' => 'Add JWT authentication.',
                'priority' => 'high',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Implement login')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('message', 'Task created successfully.');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Implement login',
            'project_id' => $this->project->id,
        ]);
    }

    public function test_task_defaults_to_todo_and_medium(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->taskUrl(), [
                'title' => 'Default values task',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'todo')
            ->assertJsonPath('data.priority', 'medium');
    }

    public function test_user_can_create_task_with_assignee(): void
    {
        $assignee = User::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->taskUrl(), [
                'title' => 'Assigned task',
                'assigned_to' => $assignee->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.assignee.id', $assignee->id);
    }

    public function test_store_fails_without_title(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->taskUrl(), [
                'description' => 'No title here.',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_fails_with_invalid_priority(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->taskUrl(), [
                'title' => 'Bad priority',
                'priority' => 'super_urgent',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_store_fails_with_nonexistent_assignee(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->taskUrl(), [
                'title' => 'Ghost assignee',
                'assigned_to' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['assigned_to']);
    }

    public function test_store_fails_with_past_due_date(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson($this->taskUrl(), [
                'title' => 'Late task',
                'due_date' => '2020-01-01',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    public function test_user_cannot_create_task_in_others_project(): void
    {
        $otherProject = Project::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/projects/{$otherProject->id}/tasks", [
                'title' => 'Should fail',
            ]);

        $response->assertStatus(403);
    }

    // ── Show ─────────────────────────────────────────────

    public function test_user_can_view_task(): void
    {
        $task = Task::factory()->create(['project_id' => $this->project->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->taskUrl($task));

        $response->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonStructure([
                'data' => ['id', 'title', 'description', 'status', 'priority', 'due_date', 'project', 'assignee', 'created_at', 'updated_at'],
            ]);
    }

    public function test_user_cannot_view_task_from_others_project(): void
    {
        $otherProject = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $otherProject->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/projects/{$otherProject->id}/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_show_returns_404_for_nonexistent_task(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson($this->taskUrl() . '/99999');

        $response->assertStatus(404);
    }

    // ── Update ───────────────────────────────────────────

    public function test_user_can_update_task(): void
    {
        $task = Task::factory()->create(['project_id' => $this->project->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson($this->taskUrl($task), [
                'title' => 'Updated title',
                'status' => 'in_progress',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_user_can_reassign_task(): void
    {
        $task = Task::factory()->create(['project_id' => $this->project->id]);
        $newAssignee = User::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson($this->taskUrl($task), [
                'assigned_to' => $newAssignee->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.assignee.id', $newAssignee->id);
    }

    public function test_user_cannot_update_task_from_others_project(): void
    {
        $otherProject = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $otherProject->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/projects/{$otherProject->id}/tasks/{$task->id}", [
                'title' => 'Hacked',
            ]);

        $response->assertStatus(403);
    }

    // ── Delete ───────────────────────────────────────────

    public function test_user_can_delete_task(): void
    {
        $task = Task::factory()->create(['project_id' => $this->project->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson($this->taskUrl($task));

        $response->assertOk()
            ->assertJsonPath('message', 'Task deleted successfully.');

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_user_cannot_delete_task_from_others_project(): void
    {
        $otherProject = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $otherProject->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/projects/{$otherProject->id}/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    // ── Global Index ─────────────────────────────────────

    public function test_user_can_list_all_tasks_globally(): void
    {
        $project2 = Project::factory()->create(['user_id' => $this->user->id]);

        Task::factory()->count(2)->create(['project_id' => $this->project->id]);
        Task::factory()->count(3)->create(['project_id' => $project2->id]);
        Task::factory()->count(4)->create(); // other user's tasks

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/tasks');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_global_index_can_filter_by_status(): void
    {
        Task::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'status' => 'done',
        ]);
        Task::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'todo',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/tasks?status=done');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    // ── Permission ───────────────────────────────────────

    public function test_member_can_create_tasks(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $project = Project::factory()->create(['user_id' => $member->id]);

        $response = $this->actingAs($member, 'sanctum')
            ->postJson("/api/v1/projects/{$project->id}/tasks", [
                'title' => 'Member task',
            ]);

        $response->assertStatus(201);
    }

    public function test_member_cannot_delete_tasks(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $project = Project::factory()->create(['user_id' => $member->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($member, 'sanctum')
            ->deleteJson("/api/v1/projects/{$project->id}/tasks/{$task->id}");

        $response->assertStatus(403);
    }
}