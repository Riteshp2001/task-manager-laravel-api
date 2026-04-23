<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function __construct(
        protected OverdueRuleService $overdueRuleService
    ) {
    }

    public function getVisibleTasks(User $user, Project $project): Collection
    {
        $query = Task::query()
            ->where('project_id', $project->id)
            ->with(['assignee', 'creator'])
            ->orderBy('due_date');

        if (! $user->isAdmin()) {
            $query->where('assigned_to', $user->id);
        }

        $tasks = $query->get();

        $this->syncOverdueStatuses($tasks);

        return $query->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(User $user, Project $project, array $data): Task
    {
        $task = Task::query()->create([
            'project_id' => $project->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'],
            'due_date' => $data['due_date'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_by' => $user->id,
            'status' => Task::STATUS_TODO,
        ]);

        $this->syncOverdueStatuses(new Collection([$task]), true);

        return $task->fresh(['assignee', 'creator']);
    }

    public function updateStatus(User $user, Task $task, string $status): Task
    {
        if (! $user->isAdmin() && $task->assigned_to !== $user->id) {
            throw new AuthorizationException('You can only update tasks assigned to you.');
        }

        $this->syncOverdueStatuses(new Collection([$task]), true);

        $task->refresh();

        $decision = $this->overdueRuleService->validateTransition($task, $status, $user->role);

        if (! $decision['allowed']) {
            throw ValidationException::withMessages([
                'status' => [$decision['reason'] ?: 'That status change is not allowed.'],
            ]);
        }

        $task->update([
            'status' => $status,
        ]);

        return $task->fresh(['assignee', 'creator']);
    }

    public function syncAllOverdueTasks(bool $strict = false): int
    {
        $tasks = Task::query()
            ->where('status', '!=', Task::STATUS_DONE)
            ->get(['id', 'status', 'due_date']);

        return $this->syncOverdueStatuses($tasks, $strict);
    }

    public function syncOverdueStatuses(Collection $tasks, bool $strict = false): int
    {
        if ($tasks->isEmpty()) {
            return 0;
        }

        $evaluatedTasks = $this->overdueRuleService->evaluateOverdue(
            $tasks->map(function (Task $task): array {
                return [
                    'id' => $task->id,
                    'status' => $task->status,
                    'due_date' => optional($task->due_date)->toIso8601String(),
                ];
            })->values()->all(),
            $strict
        );

        $taskIds = collect($evaluatedTasks)
            ->filter(function (array $task): bool {
                return (bool) ($task['should_mark_overdue'] ?? false);
            })
            ->pluck('id')
            ->all();

        if ($taskIds === []) {
            return 0;
        }

        return Task::query()
            ->whereIn('id', $taskIds)
            ->where('status', '!=', Task::STATUS_OVERDUE)
            ->update([
                'status' => Task::STATUS_OVERDUE,
                'updated_at' => now(),
            ]);
    }
}
