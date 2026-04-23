<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

class ProjectService
{
    public function getVisibleProjects(User $user): Collection
    {
        $query = Project::query()
            ->with('creator')
            ->latest();

        if ($user->isAdmin()) {
            return $query
                ->withCount('tasks')
                ->get();
        }

        return $query
            ->whereHas('tasks', function ($taskQuery) use ($user) {
                $taskQuery->where('assigned_to', $user->id);
            })
            ->withCount([
                'tasks as task_count' => function ($taskQuery) use ($user) {
                    $taskQuery->where('assigned_to', $user->id);
                },
            ])
            ->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(User $user, array $data): Project
    {
        return Project::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'created_by' => $user->id,
        ])->load('creator');
    }

    public function getVisibleProject(User $user, Project $project): Project
    {
        if ($user->isAdmin()) {
            return $project->load('creator')->loadCount('tasks');
        }

        $hasAssignedTask = $project->tasks()
            ->where('assigned_to', $user->id)
            ->exists();

        if (! $hasAssignedTask) {
            throw new AuthorizationException('You cannot view this project.');
        }

        return $project
            ->load('creator')
            ->loadCount([
                'tasks as task_count' => function ($taskQuery) use ($user) {
                    $taskQuery->where('assigned_to', $user->id);
                },
            ]);
    }
}
