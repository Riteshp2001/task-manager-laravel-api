<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_project(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/projects', [
            'name' => 'Assignment Project',
            'description' => 'Created in a feature test.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.project.name', 'Assignment Project');

        $this->assertDatabaseHas('projects', [
            'name' => 'Assignment Project',
            'created_by' => $admin->id,
        ]);
    }

    public function test_member_only_sees_projects_with_assigned_tasks(): void
    {
        $member = User::factory()->create();
        $otherUser = User::factory()->create();
        $projectForMember = Project::factory()->create();
        $projectForOtherUser = Project::factory()->create();

        Task::factory()->create([
            'project_id' => $projectForMember->id,
            'assigned_to' => $member->id,
        ]);

        Task::factory()->create([
            'project_id' => $projectForOtherUser->id,
            'assigned_to' => $otherUser->id,
        ]);

        Sanctum::actingAs($member);

        $response = $this->getJson('/api/projects');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.projects')
            ->assertJsonPath('data.projects.0.id', $projectForMember->id);
    }
}
