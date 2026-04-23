<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

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

        $response = $this->client()->post('/api/rules/evaluate-overdue/', [
            'tasks' => $tasks,
        ]);

        if ($response->failed()) {
            return $this->handleFailure(
                $response->json('message') ?: 'Unable to evaluate overdue tasks right now.',
                $strict
            );
        }

        return $response->json('data.tasks', []);
    }

    /**
     * @return array{allowed: bool, reason: string, resolved_status: string}
     */
    public function validateTransition(Task $task, string $nextStatus, string $actorRole): array
    {
        $response = $this->client()->post('/api/rules/validate-transition/', [
            'task_id' => $task->id,
            'current_status' => $task->status,
            'next_status' => $nextStatus,
            'due_date' => optional($task->due_date)->toIso8601String(),
            'actor_role' => $actorRole,
        ]);

        if ($response->failed()) {
            $message = $response->json('message') ?: 'Unable to validate the task transition right now.';

            throw new RuntimeException($message);
        }

        return [
            'allowed' => (bool) $response->json('data.allowed', false),
            'reason' => (string) $response->json('data.reason', ''),
            'resolved_status' => (string) $response->json('data.resolved_status', $task->status),
        ];
    }

    /**
     * @return array<int, array{id:int, should_mark_overdue:bool, resolved_status:string}>
     */
    protected function handleFailure(string $message, bool $strict): array
    {
        if ($strict) {
            throw new RuntimeException($message);
        }

        Log::warning('Overdue rules service request failed.', [
            'message' => $message,
        ]);

        return [];
    }

    protected function client()
    {
        $client = Http::baseUrl(config('services.overdue.url'))
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
