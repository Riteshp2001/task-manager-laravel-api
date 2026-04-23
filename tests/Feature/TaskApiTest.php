<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_move_overdue_task_back_to_in_progress(): void
    {
        $member = User::factory()->create();
        $task = Task::factory()->create([
            'assigned_to' => $member->id,
            'status' => Task::STATUS_OVERDUE,
            'due_date' => now()->subDay(),
        ]);

        Http::fake([
            '*/api/rules/evaluate-overdue/' => Http::response([
                'success' => true,
                'message' => 'Rules checked.',
                'data' => [
                    'tasks' => [
                        [
                            'id' => $task->id,
                            'should_mark_overdue' => true,
                            'resolved_status' => Task::STATUS_OVERDUE,
                        ],
                    ],
                ],
            ]),
            '*/api/rules/validate-transition/' => Http::response([
                'success' => true,
                'message' => 'Transition rejected.',
                'data' => [
                    'allowed' => false,
                    'reason' => 'Overdue tasks cannot move back to IN_PROGRESS.',
                    'resolved_status' => Task::STATUS_OVERDUE,
                ],
            ]),
        ]);

        Sanctum::actingAs($member);

        $response = $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => Task::STATUS_IN_PROGRESS,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_close_an_overdue_task(): void
    {
        $admin = User::factory()->admin()->create();
        $task = Task::factory()->create([
            'status' => Task::STATUS_OVERDUE,
            'due_date' => now()->subDay(),
        ]);

        Http::fake([
            '*/api/rules/evaluate-overdue/' => Http::response([
                'success' => true,
                'message' => 'Rules checked.',
                'data' => [
                    'tasks' => [
                        [
                            'id' => $task->id,
                            'should_mark_overdue' => true,
                            'resolved_status' => Task::STATUS_OVERDUE,
                        ],
                    ],
                ],
            ]),
            '*/api/rules/validate-transition/' => Http::response([
                'success' => true,
                'message' => 'Transition allowed.',
                'data' => [
                    'allowed' => true,
                    'reason' => '',
                    'resolved_status' => Task::STATUS_OVERDUE,
                ],
            ]),
        ]);

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => Task::STATUS_DONE,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.task.status', Task::STATUS_DONE);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => Task::STATUS_DONE,
        ]);
    }

    public function test_project_tasks_endpoint_updates_overdue_tasks_before_returning(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => Task::STATUS_TODO,
            'due_date' => now()->subHour(),
        ]);

        Http::fake([
            '*/api/rules/evaluate-overdue/' => Http::response([
                'success' => true,
                'message' => 'Rules checked.',
                'data' => [
                    'tasks' => [
                        [
                            'id' => $task->id,
                            'should_mark_overdue' => true,
                            'resolved_status' => Task::STATUS_OVERDUE,
                        ],
                    ],
                ],
            ]),
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/projects/{$project->id}/tasks");

        $response
            ->assertOk()
            ->assertJsonPath('data.tasks.0.status', Task::STATUS_OVERDUE);
    }
}
