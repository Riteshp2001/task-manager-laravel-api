<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class OverdueRuleService
{
    /**
     * @param array<int, array{id:int, status:string, due_date:?string}> $tasks
     * @return array<int, array{id:int, should_mark_overdue:bool, resolved_status:string}>
     */
    public function evaluateOverdue(array $tasks, bool $strict = false): array
    {
        if ($tasks === []) {
            return [];
        }

        try {
            $response = $this->client()->post('/api/rules/evaluate-overdue/', [
                'tasks' => $tasks,
            ]);

            if ($response->failed()) {
                throw new RuntimeException(
                    $response->json('message') ?: 'Unable to evaluate overdue tasks right now.'
                );
            }

            return $response->json('data.tasks', []);
        } catch (Throwable $exception) {
            return $this->fallbackEvaluateOverdue($tasks, $exception->getMessage(), $strict);
        }
    }

    /**
     * @return array{allowed: bool, reason: string, resolved_status: string}
     */
    public function validateTransition(Task $task, string $nextStatus, string $actorRole): array
    {
        try {
            $response = $this->client()->post('/api/rules/validate-transition/', [
                'task_id' => $task->id,
                'current_status' => $task->status,
                'next_status' => $nextStatus,
                'due_date' => optional($task->due_date)->toIso8601String(),
                'actor_role' => $actorRole,
            ]);

            if ($response->failed()) {
                throw new RuntimeException(
                    $response->json('message') ?: 'Unable to validate the task transition right now.'
                );
            }

            return [
                'allowed' => (bool) $response->json('data.allowed', false),
                'reason' => (string) $response->json('data.reason', ''),
                'resolved_status' => (string) $response->json('data.resolved_status', $task->status),
            ];
        } catch (Throwable $exception) {
            return $this->fallbackValidateTransition($task, $nextStatus, $actorRole, $exception->getMessage());
        }
    }

    /**
     * @return array<int, array{id:int, should_mark_overdue:bool, resolved_status:string}>
     */
    protected function fallbackEvaluateOverdue(array $tasks, string $message, bool $strict): array
    {
        Log::warning('Overdue rules service request failed.', [
            'message' => $message,
            'strict' => $strict,
            'fallback' => true,
        ]);

        return array_map(function (array $task): array {
            $status = (string) ($task['status'] ?? '');
            $isOverdue = $status !== Task::STATUS_DONE && $this->dueDateIsPast($task['due_date'] ?? null);

            return [
                'id' => $task['id'] ?? null,
                'should_mark_overdue' => $isOverdue,
                'resolved_status' => $isOverdue ? Task::STATUS_OVERDUE : $status,
            ];
        }, $tasks);
    }

    /**
     * @return array{allowed: bool, reason: string, resolved_status: string}
     */
    protected function fallbackValidateTransition(
        Task $task,
        string $nextStatus,
        string $actorRole,
        string $message
    ): array {
        Log::warning('Overdue rules service transition validation failed.', [
            'message' => $message,
            'task_id' => $task->id,
            'fallback' => true,
        ]);

        $isOverdue = $task->status !== Task::STATUS_DONE && (bool) optional($task->due_date)->isPast();
        $allowed = true;
        $reason = '';

        if ($isOverdue && $nextStatus === Task::STATUS_IN_PROGRESS) {
            $allowed = false;
            $reason = 'Overdue tasks cannot move back to IN_PROGRESS.';
        } elseif ($isOverdue && $nextStatus === Task::STATUS_DONE && $actorRole !== User::ROLE_ADMIN) {
            $allowed = false;
            $reason = 'Only admins can close overdue tasks.';
        }

        return [
            'allowed' => $allowed,
            'reason' => $reason,
            'resolved_status' => $allowed ? $nextStatus : $task->status,
        ];
    }

    protected function dueDateIsPast(mixed $dueDate): bool
    {
        if (! $dueDate) {
            return false;
        }

        try {
            return CarbonImmutable::parse($dueDate)->isPast();
        } catch (Throwable) {
            return false;
        }
    }

    protected function client()
    {
        $client = Http::baseUrl((string) config('services.overdue.url'))
            ->timeout((int) config('services.overdue.timeout', 10))
            ->acceptJson();

        if (config('services.overdue.key')) {
            $client = $client->withHeaders([
                'X-Service-Key' => config('services.overdue.key'),
            ]);
        }

        return $client;
    }
}
