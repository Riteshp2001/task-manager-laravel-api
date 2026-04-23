<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Assignment Admin',
                'password' => 'password123',
                'role' => User::ROLE_ADMIN,
            ]
        );

        $member = User::query()->updateOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'Assignment Member',
                'password' => 'password123',
                'role' => User::ROLE_USER,
            ]
        );

        $secondMember = User::query()->updateOrCreate(
            ['email' => 'member.two@example.com'],
            [
                'name' => 'Second Member',
                'password' => 'password123',
                'role' => User::ROLE_USER,
            ]
        );

        $project = Project::query()->updateOrCreate(
            ['name' => 'Website Refresh'],
            [
                'description' => 'Simple seeded project for quick assignment review.',
                'created_by' => $admin->id,
            ]
        );

        Task::query()->updateOrCreate(
            ['title' => 'Prepare homepage wireframe'],
            [
                'project_id' => $project->id,
                'description' => 'Draft a clean wireframe for the landing page.',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_HIGH,
                'due_date' => now()->subDay(),
                'assigned_to' => $member->id,
                'created_by' => $admin->id,
            ]
        );

        Task::query()->updateOrCreate(
            ['title' => 'Build task table component'],
            [
                'project_id' => $project->id,
                'description' => 'Implement the project detail task list.',
                'status' => Task::STATUS_IN_PROGRESS,
                'priority' => Task::PRIORITY_MEDIUM,
                'due_date' => now()->addDays(2),
                'assigned_to' => $secondMember->id,
                'created_by' => $admin->id,
            ]
        );

        Task::query()->updateOrCreate(
            ['title' => 'Review status transition rules'],
            [
                'project_id' => $project->id,
                'description' => 'Confirm overdue tasks follow the expected restrictions.',
                'status' => Task::STATUS_DONE,
                'priority' => Task::PRIORITY_LOW,
                'due_date' => now()->subDays(2),
                'assigned_to' => $member->id,
                'created_by' => $admin->id,
            ]
        );
    }
}
